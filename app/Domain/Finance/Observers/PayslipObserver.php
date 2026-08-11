<?php

namespace App\Domain\Finance\Observers;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\LockedFinancialRecordException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;

/**
 * A payslip is locked by its run, never independently (BR-610).
 *
 * The run's status is read straight from the database rather than through the
 * loaded relation: an in-memory `payrollRun` could be stale, and a guard on a
 * permanent financial record must not depend on what happens to be hydrated.
 */
class PayslipObserver
{
    public function updating(Payslip $payslip): void
    {
        $this->guard($payslip, 'update');
    }

    public function deleting(Payslip $payslip): void
    {
        $this->guard($payslip, 'deletion');
    }

    public function forceDeleting(Payslip $payslip): void
    {
        $this->guard($payslip, 'permanent deletion');
    }

    private function guard(Payslip $payslip, string $operation): void
    {
        if ($this->runIsLocked((int) $payslip->payroll_run_id)) {
            throw LockedFinancialRecordException::forPayslip((int) $payslip->id, $operation);
        }
    }

    private function runIsLocked(int $payrollRunId): bool
    {
        // Builder::value() applies the model's casts, so this is already a
        // PayrollRunStatus — not a string to be re-parsed.
        $status = PayrollRun::withTrashed()
            ->whereKey($payrollRunId)
            ->value('status');

        return $status instanceof PayrollRunStatus && $status->isLocked();
    }
}
