<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Finance\Exceptions\PayrollAdjustmentException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\PayrollRunAdjustment;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItem;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Records a correction to a LOCKED payroll run (BR-603).
 *
 * The correction never touches the run being corrected — that run is immutable
 * and the observers would reject the write regardless. Instead it:
 *
 *   1. writes a `payroll_run_adjustments` audit row, and
 *   2. adds a signed line item to the SAME employee's payslip on a later DRAFT
 *      run, so the money actually moves in the next cycle.
 *
 * That second step is what makes this a real correction mechanism rather than
 * a note: without it the adjustment would be recorded and never paid.
 */
final class RecordPayrollAdjustment
{
    public function handle(
        PayrollRun $targetRun,
        Payslip $originalPayslip,
        int $amountMinorUnits,
        string $reason,
        ?User $author = null,
    ): PayrollRunAdjustment {
        if (! $targetRun->status->isEditable()) {
            throw PayrollAdjustmentException::targetRunNotDraft($targetRun->period);
        }

        $originalRun = $originalPayslip->payrollRun;

        if ($originalRun === null || ! $originalRun->status->isLocked()) {
            throw PayrollAdjustmentException::originalNotLocked();
        }

        if ($originalRun->id === $targetRun->id) {
            throw PayrollAdjustmentException::cannotAdjustSelf($targetRun->period);
        }

        if ($amountMinorUnits === 0) {
            throw PayrollAdjustmentException::zeroAmount();
        }

        if (trim($reason) === '') {
            throw PayrollAdjustmentException::reasonRequired();
        }

        // The employee must actually be on the carrying run, or the correction
        // has nowhere to land and would silently never be paid.
        $carryingPayslip = $targetRun->payslips()
            ->where('employee_id', $originalPayslip->employee_id)
            ->first();

        if ($carryingPayslip === null) {
            throw PayrollAdjustmentException::employeeNotInTargetRun(
                $originalPayslip->employee_name,
                $targetRun->period,
            );
        }

        return DB::transaction(function () use (
            $targetRun, $originalPayslip, $originalRun, $carryingPayslip, $amountMinorUnits, $reason, $author
        ): PayrollRunAdjustment {
            $adjustment = PayrollRunAdjustment::query()->create([
                'payroll_run_id' => $targetRun->id,
                'original_payslip_id' => $originalPayslip->id,
                'original_period' => $originalRun->period,
                'employee_id' => $originalPayslip->employee_id,
                'employee_name' => $originalPayslip->employee_name,
                'reason' => $reason,
                'amount' => $amountMinorUnits,
                'created_by' => $author?->id,
            ]);

            PayslipLineItem::query()->create([
                'payslip_id' => $carryingPayslip->id,
                'payslip_line_item_type_id' => null,
                'label' => "تسوية فترة {$originalRun->period} — {$reason}",
                'kind' => $amountMinorUnits >= 0
                    ? PayslipLineItemKind::Allowance
                    : PayslipLineItemKind::Deduction,
                'amount' => $amountMinorUnits,
                'sort_order' => 999,
            ]);

            $this->refreshPayslip($carryingPayslip);
            (new RecalculatePayrollRunTotals)->handle($targetRun);

            return $adjustment;
        });
    }

    /**
     * Re-roll the carrying payslip's allowance/deduction columns and net.
     * Base and absence figures come from the Work Ledger and are untouched.
     */
    private function refreshPayslip(Payslip $payslip): void
    {
        $payslip->load('lineItems');

        $allowances = (int) $payslip->lineItems->where('amount', '>=', 0)->sum('amount');
        $deductions = (int) $payslip->lineItems->where('amount', '<', 0)->sum('amount');
        $gross = $payslip->base_amount + $allowances;

        $payslip->update([
            'allowances_total' => $allowances,
            'deductions_total' => $deductions,
            'gross_amount' => $gross,
            'net_amount' => $gross + $payslip->absence_deduction + $deductions,
        ]);
    }

    /**
     * Locked runs that can still be corrected — anything approved or paid.
     *
     * @return Collection<int, PayrollRun>
     */
    public static function correctableRuns()
    {
        return PayrollRun::query()
            ->whereIn('status', [PayrollRunStatus::Approved->value, PayrollRunStatus::Paid->value])
            ->latest('period')
            ->get();
    }
}
