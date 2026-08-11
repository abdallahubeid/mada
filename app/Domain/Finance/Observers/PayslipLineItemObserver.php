<?php

namespace App\Domain\Finance\Observers;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\LockedFinancialRecordException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItem;

/**
 * A line item is locked by its payslip's run (BR-610).
 *
 * Line items are where a correction would most plausibly be attempted — "just
 * fix the allowance on last month's payslip" — which is exactly why the guard
 * sits here and not only on the run.
 */
class PayslipLineItemObserver
{
    public function updating(PayslipLineItem $lineItem): void
    {
        $this->guard($lineItem, 'update');
    }

    public function deleting(PayslipLineItem $lineItem): void
    {
        $this->guard($lineItem, 'deletion');
    }

    public function forceDeleting(PayslipLineItem $lineItem): void
    {
        $this->guard($lineItem, 'permanent deletion');
    }

    private function guard(PayslipLineItem $lineItem, string $operation): void
    {
        $runId = Payslip::withTrashed()
            ->whereKey($lineItem->payslip_id)
            ->value('payroll_run_id');

        if ($runId === null) {
            return;
        }

        // Builder::value() applies the model's casts, so this is already a
        // PayrollRunStatus — not a string to be re-parsed.
        $status = PayrollRun::withTrashed()
            ->whereKey($runId)
            ->value('status');

        if ($status instanceof PayrollRunStatus && $status->isLocked()) {
            throw LockedFinancialRecordException::forLineItem((int) $lineItem->id, $operation);
        }
    }
}
