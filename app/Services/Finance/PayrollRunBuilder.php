<?php

namespace App\Services\Finance;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Finance\Exceptions\PayrollRunException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItem;
use App\Domain\Finance\Models\PayslipLineItemType;
use App\Domain\Finance\Support\WorkLedgerSummary;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Assembles a DRAFT payroll run and its payslips (BR-603).
 *
 * Every guard runs before a single row is written, and each one fails loudly:
 * a run built on unresolved ledger days or unpriced contracts would produce
 * plausible-looking wrong numbers in a record the system keeps forever.
 *
 * This service only ever produces a draft. Submitting for approval, approving
 * (which locks and snapshots) and marking paid are separate transitions — a
 * builder that could also approve would defeat maker-checker (ADR-09).
 */
final class PayrollRunBuilder
{
    public function __construct(
        private readonly PayslipCalculator $calculator,
        private readonly WorkLedgerSummarizer $summarizer,
    ) {}

    /**
     * @param  string  $period  YYYY-MM
     *
     * @throws PayrollRunException
     */
    public function build(string $period, ?User $maker = null): PayrollRun
    {
        [$start, $end] = $this->periodBounds($period);

        $this->guardNoLiveRun($period);

        $contracts = $this->payableContracts();

        if ($contracts->isEmpty()) {
            throw PayrollRunException::noActiveContracts($period);
        }

        $this->guardContractsPriced($contracts);
        $currency = $this->resolveCurrency($contracts);
        $this->guardLedgerResolved($period, $start, $end);

        return DB::transaction(function () use ($period, $start, $end, $contracts, $currency, $maker): PayrollRun {
            $run = PayrollRun::create([
                'period' => $period,
                'period_start' => $start->toDateString(),
                'period_end' => $end->toDateString(),
                'status' => PayrollRunStatus::Draft,
                'currency' => $currency,
                'maker_id' => $maker?->id,
            ]);

            $employeeIds = $contracts->pluck('employee_id')->map(intval(...))->all();
            $summaries = $this->summarizer->summarize($employeeIds, $start, $end);
            $periodScheduledDays = $this->summarizer->periodScheduledDays($start, $end);
            $lineItemTypes = $this->activeLineItemTypes();

            $totals = [
                'total_base' => 0,
                'total_absence_deduction' => 0,
                'total_allowances' => 0,
                'total_deductions' => 0,
                'total_gross' => 0,
                'total_net' => 0,
            ];

            foreach ($contracts as $contract) {
                $employeeId = (int) $contract->employee_id;
                $summary = $summaries[$employeeId] ?? WorkLedgerSummary::empty($periodScheduledDays);

                $lineItemAmounts = $lineItemTypes
                    ->map(fn (PayslipLineItemType $type): int => $this->signedDefault($type))
                    ->all();

                $computed = $this->calculator->calculate(
                    $contract->pay_basis,
                    $contract->base_rate,
                    $summary,
                    array_values($lineItemAmounts),
                );

                $payslip = Payslip::create([
                    'payroll_run_id' => $run->id,
                    'employee_id' => $employeeId,
                    'employee_contract_id' => $contract->id,

                    // Frozen snapshot (BR-608).
                    'employee_name' => $contract->employee?->full_name ?? '—',
                    'job_title' => $contract->employee?->job_title,
                    'department_name' => $contract->employee?->department?->name,
                    'pay_basis' => $contract->pay_basis,
                    'base_rate' => $contract->base_rate,
                    'pay_currency' => $contract->pay_currency ?? $currency,
                    'period_scheduled_days' => $summary->periodScheduledDays,
                    'scheduled_days' => $summary->scheduledDays,
                    'present_days' => $summary->presentDays,
                    'excused_days' => $summary->excusedDays,
                    'absent_days' => $summary->absentDays,
                    'worked_minutes' => $summary->workedMinutes,

                    ...$computed->toArray(),
                ]);

                foreach ($lineItemTypes as $type) {
                    PayslipLineItem::create([
                        'payslip_id' => $payslip->id,
                        'payslip_line_item_type_id' => $type->id,
                        'label' => $type->name,
                        'kind' => $type->kind,
                        'amount' => $this->signedDefault($type),
                        'sort_order' => $type->sort_order,
                    ]);
                }

                $totals['total_base'] += $computed->baseAmount;
                $totals['total_absence_deduction'] += $computed->absenceDeduction;
                $totals['total_allowances'] += $computed->allowancesTotal;
                $totals['total_deductions'] += $computed->deductionsTotal;
                $totals['total_gross'] += $computed->grossAmount;
                $totals['total_net'] += $computed->netAmount;
            }

            $run->update([...$totals, 'payslip_count' => $contracts->count()]);

            return $run->refresh();
        });
    }

    /**
     * @return array{0: Carbon, 1: Carbon}
     */
    private function periodBounds(string $period): array
    {
        if (preg_match('/^\d{4}-(0[1-9]|1[0-2])$/', $period) !== 1) {
            throw PayrollRunException::invalidPeriod($period);
        }

        $start = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();

        return [$start, $start->copy()->endOfMonth()->startOfDay()];
    }

    private function guardNoLiveRun(string $period): void
    {
        $exists = PayrollRun::query()
            ->where('period', $period)
            ->where('status', '!=', PayrollRunStatus::Cancelled->value)
            ->exists();

        if ($exists) {
            throw PayrollRunException::periodAlreadyHasLiveRun($period);
        }
    }

    /**
     * Active contracts, one per employee.
     *
     * An employee holding two overlapping active contracts would otherwise
     * collide on the (run, employee) unique key mid-transaction; the most
     * recently started contract wins.
     *
     * @return Collection<int, EmployeeContract>
     */
    private function payableContracts(): Collection
    {
        return EmployeeContract::query()
            ->with(['employee.department'])
            ->where('status', ContractStatus::Active->value)
            ->orderByDesc('start_date')
            ->orderByDesc('id')
            ->get()
            ->unique('employee_id')
            ->values();
    }

    /**
     * @param  Collection<int, EmployeeContract>  $contracts
     */
    private function guardContractsPriced(Collection $contracts): void
    {
        $unpriced = $contracts
            ->filter(fn (EmployeeContract $contract): bool => $contract->hasUnsetPayRate())
            ->map(fn (EmployeeContract $contract): string => $contract->employee?->full_name ?? "#{$contract->employee_id}")
            ->values()
            ->all();

        if ($unpriced !== []) {
            throw PayrollRunException::contractsMissingPayRate($unpriced);
        }
    }

    /**
     * @param  Collection<int, EmployeeContract>  $contracts
     */
    private function resolveCurrency(Collection $contracts): string
    {
        $currencies = $contracts
            ->map(fn (EmployeeContract $contract): ?string => $contract->pay_currency)
            ->filter()
            ->unique()
            ->values()
            ->all();

        if (count($currencies) > 1) {
            throw PayrollRunException::mixedCurrencies($currencies);
        }

        return $currencies[0]
            ?? OrgSetting::query()->value('currency')
            ?? 'SAR';
    }

    private function guardLedgerResolved(string $period, Carbon $start, Carbon $end): void
    {
        // Emptiness first: zero rows passes the unresolved-days check trivially,
        // then sends the calculator down its no-ledger fallback and pays
        // everyone in full. See PayrollRunException::ledgerEmpty().
        if ($this->summarizer->periodScheduledDays($start, $end) === 0) {
            throw PayrollRunException::ledgerEmpty($period);
        }

        $unresolved = $this->summarizer->unresolvedDayCount($start, $end);

        if ($unresolved > 0) {
            throw PayrollRunException::ledgerNotReconciled($period, $unresolved);
        }
    }

    /**
     * @return Collection<int, PayslipLineItemType>
     */
    private function activeLineItemTypes(): Collection
    {
        return PayslipLineItemType::query()
            ->active()
            ->where('default_amount', '!=', 0)
            ->orderBy('sort_order')
            ->orderBy('id')
            ->get();
    }

    /**
     * Force a line item's sign to agree with its kind.
     *
     * A deduction type misconfigured with a positive default would otherwise
     * silently ADD money to every payslip in the run. Deriving the sign from
     * the kind makes that unrepresentable rather than merely invalid.
     */
    private function signedDefault(PayslipLineItemType $type): int
    {
        $magnitude = abs($type->default_amount);

        return $type->kind === PayslipLineItemKind::Deduction ? -$magnitude : $magnitude;
    }
}
