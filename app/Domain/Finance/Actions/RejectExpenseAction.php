<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Exceptions\ExpenseTransitionException;
use App\Domain\Finance\Models\Expense;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * pending_approval -> rejected, with the reason recorded (BR-905).
 *
 * Rejection is not terminal for the claimant: a rejected expense is editable
 * again, so a corrected claim is a resubmission rather than a fresh record —
 * which keeps the approval history attached to one subject.
 */
final class RejectExpenseAction
{
    public function handle(Expense $expense, User $decidedBy, string $reason): Expense
    {
        if ($expense->status !== ExpenseStatus::PendingApproval) {
            throw ExpenseTransitionException::notAwaitingDecision($expense->title, $expense->status);
        }

        if ($expense->submitted_by !== null && (int) $expense->submitted_by === (int) $decidedBy->id) {
            throw ExpenseTransitionException::submitterCannotDecide($expense->title);
        }

        if (trim($reason) === '') {
            throw ExpenseTransitionException::rejectionNeedsReason($expense->title);
        }

        return DB::transaction(function () use ($expense, $decidedBy, $reason): Expense {
            $expense->currentApproval?->update([
                'status' => ApprovalStatus::Rejected,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
                'reason' => $reason,
            ]);

            $expense->update([
                'status' => ExpenseStatus::Rejected,
                'decided_by' => $decidedBy->id,
                'decided_at' => now(),
                'rejection_reason' => $reason,
            ]);

            return $expense->refresh();
        });
    }
}
