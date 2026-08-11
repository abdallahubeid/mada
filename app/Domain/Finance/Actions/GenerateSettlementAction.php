<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Exceptions\SettlementException;
use App\Domain\Finance\Models\FinanceSetting;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\WorkLedgerDayType;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Models\WorkLedgerEntry;
use App\Models\User;
use App\Services\Finance\OffboardingCalculator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Builds a DRAFT settlement for a departing employee (BR-606).
 *
 * Produces a draft only. Approval and disbursement are separate transitions —
 * the same maker-checker discipline payroll uses (ADR-09), because a settlement
 * is the single largest payment most employees ever receive from an employer.
 */
final class GenerateSettlementAction
{
    public function __construct(private readonly OffboardingCalculator $calculator) {}

    public function handle(
        Employee $employee,
        Carbon $lastWorkingDay,
        OffboardingReason $reason,
        int $loanDeduction = 0,
        int $otherDeduction = 0,
        ?User $author = null,
        ?string $notes = null,
    ): OffboardingSettlement {
        $this->guardNoLiveSettlement($employee);

        $contract = EmployeeContract::query()
            ->where('employee_id', $employee->id)
            ->where('status', ContractStatus::Active->value)
            ->latest('start_date')
            ->first();

        if ($contract === null) {
            throw SettlementException::noActiveContract($employee->full_name);
        }

        if ($contract->hasUnsetPayRate()) {
            throw SettlementException::contractHasNoPayRate($employee->full_name);
        }

        $serviceMonths = $this->calculator->serviceMonths($employee->joining_date, $lastWorkingDay);
        $scheduledDays = $this->scheduledDaysInMonth($lastWorkingDay);
        $workedDays = $this->workedDaysInFinalMonth($employee->id, $lastWorkingDay);

        /*
         * The one place the tenant's configured EOSB rules are read. The
         * calculator is pure and never looks them up itself; resolving here
         * also means the exact rules used are available to snapshot below.
         */
        $policy = FinanceSetting::current()->eosbPolicy();

        $breakdown = $this->calculator->calculate(
            payBasis: $contract->pay_basis,
            baseRate: $contract->base_rate,
            serviceMonths: $serviceMonths,
            reason: $reason,
            unusedLeaveDays: $this->unusedLeaveDays($employee->id, $lastWorkingDay),
            workedDaysInFinalMonth: $workedDays,
            scheduledDaysInFinalMonth: $scheduledDays,
            loanDeduction: $loanDeduction,
            otherDeduction: $otherDeduction,
            policy: $policy,
        );

        return DB::transaction(fn (): OffboardingSettlement => OffboardingSettlement::query()->create([
            'employee_id' => $employee->id,
            'employee_contract_id' => $contract->id,

            // Snapshot — the settlement must render forever without these joins.
            'employee_name' => $employee->full_name,
            'job_title' => $employee->job_title,
            'department_name' => $employee->department?->name,
            'pay_basis' => $contract->pay_basis,
            'base_rate' => $contract->base_rate,
            'currency' => $contract->pay_currency ?? OrgSetting::query()->value('currency') ?? 'SAR',
            'joining_date' => $employee->joining_date,
            'last_working_day' => $lastWorkingDay->toDateString(),
            'service_months' => $serviceMonths,
            'unused_leave_days' => $this->unusedLeaveDays($employee->id, $lastWorkingDay),

            // The rules themselves are part of the snapshot: without them a
            // locked settlement stops reconciling the moment a rate is edited.
            'eosb_policy' => $policy->toArray(),
            'reason' => $reason,
            'status' => SettlementStatus::Draft,
            'notes' => $notes,
            'created_by' => $author?->id,
            ...$breakdown->toArray(),
        ]));
    }

    private function guardNoLiveSettlement(Employee $employee): void
    {
        $exists = OffboardingSettlement::query()
            ->where('employee_id', $employee->id)
            ->where('status', '!=', SettlementStatus::Cancelled->value)
            ->exists();

        if ($exists) {
            throw SettlementException::alreadySettled($employee->full_name);
        }
    }

    /**
     * Remaining balance across every leave type, in the year of departure.
     */
    private function unusedLeaveDays(int $employeeId, Carbon $lastWorkingDay): int
    {
        return (int) LeaveType::query()
            ->get()
            ->sum(fn (LeaveType $type): int => $type->remainingDaysFor($employeeId, (int) $lastWorkingDay->year));
    }

    /**
     * Working days the org scheduled in the departure month, from the Work
     * Ledger — the same basis payroll prorates against (BR-605), so a
     * settlement and a payslip for the same month agree.
     */
    private function scheduledDaysInMonth(Carbon $lastWorkingDay): int
    {
        return WorkLedgerEntry::query()
            ->whereBetween('date', [
                $lastWorkingDay->copy()->startOfMonth()->toDateString(),
                $lastWorkingDay->copy()->endOfMonth()->toDateString(),
            ])
            ->whereIn('day_type', [
                WorkLedgerDayType::Workday->value,
                WorkLedgerDayType::Present->value,
                WorkLedgerDayType::Excused->value,
                WorkLedgerDayType::Absent->value,
            ])
            ->distinct()
            ->count('date');
    }

    private function workedDaysInFinalMonth(int $employeeId, Carbon $lastWorkingDay): int
    {
        return WorkLedgerEntry::query()
            ->where('employee_id', $employeeId)
            ->whereBetween('date', [
                $lastWorkingDay->copy()->startOfMonth()->toDateString(),
                $lastWorkingDay->toDateString(),
            ])
            ->whereIn('day_type', [
                WorkLedgerDayType::Present->value,
                WorkLedgerDayType::Excused->value,
            ])
            ->count();
    }
}
