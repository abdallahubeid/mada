<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Exceptions\SettlementException;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * approved -> paid, and the employment record CLOSES (BR-606).
 *
 * This is the step that makes offboarding real rather than cosmetic. In one
 * transaction it:
 *
 *   1. records the disbursement,
 *   2. moves the employee out of Active status,
 *   3. terminates the active contract, and
 *   4. deactivates the linked user account.
 *
 * Step 3 is what actually freezes future payroll: PayrollRunBuilder selects
 * contracts by ACTIVE status, so terminating the contract is what removes this
 * employee from every subsequent run. Setting only the employee status would
 * look right in the UI and keep paying them.
 */
final class DisburseSettlementAction
{
    public function handle(OffboardingSettlement $settlement): OffboardingSettlement
    {
        if ($settlement->status !== SettlementStatus::Approved) {
            throw SettlementException::notApproved($settlement->employee_name, $settlement->status);
        }

        return DB::transaction(function () use ($settlement): OffboardingSettlement {
            $settlement->update([
                'status' => SettlementStatus::Paid,
                'paid_at' => now(),
            ]);

            $this->closeEmployment($settlement);

            return $settlement->refresh();
        });
    }

    private function closeEmployment(OffboardingSettlement $settlement): void
    {
        $employee = Employee::query()->find($settlement->employee_id);

        if ($employee === null) {
            return;
        }

        $employee->update([
            'status' => $settlement->reason === OffboardingReason::Resignation
                ? EmployeeStatus::Resigned
                : EmployeeStatus::Suspended,
        ]);

        // The payroll-freezing step (see class docblock).
        EmployeeContract::query()
            ->where('employee_id', $employee->id)
            ->where('status', ContractStatus::Active->value)
            ->get()
            ->each(fn (EmployeeContract $contract) => $contract->update([
                'status' => ContractStatus::Terminated,
                'end_date' => $contract->end_date ?? $settlement->last_working_day,
            ]));

        // Access revocation (BR-606). The account is deactivated rather than
        // deleted: audit history, approvals and payslips all reference it.
        if ($employee->user_id !== null) {
            User::query()->whereKey($employee->user_id)->update(['is_active' => false]);
        }
    }
}
