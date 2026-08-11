<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\PayrollRunTransitionException;
use App\Domain\Finance\Models\PayrollRun;
use App\Models\User;

/**
 * pending_approval -> draft, with the reason recorded (BR-603).
 *
 * Rejection returns the run to the maker rather than terminating it: the
 * period still has to be paid, and BR-611 would block a replacement run while
 * this one remains live.
 */
final class RejectPayrollRun
{
    public function handle(PayrollRun $run, User $decidedBy, string $reason): PayrollRun
    {
        if ($run->status !== PayrollRunStatus::PendingApproval) {
            throw PayrollRunTransitionException::notInState(
                $run->period, 'rejection', PayrollRunStatus::PendingApproval, $run->status
            );
        }

        if (trim($reason) === '') {
            throw PayrollRunTransitionException::rejectionNeedsReason($run->period);
        }

        $run->update([
            'status' => PayrollRunStatus::Draft,
            'rejection_reason' => $reason,
            'approver_id' => null,
            'approved_at' => null,
        ]);

        return $run->refresh();
    }
}
