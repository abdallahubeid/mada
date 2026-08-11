<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Exceptions\ExpenseTransitionException;
use App\Domain\Finance\Models\Expense;
use App\Domain\Tenancy\ApprovableCatalog;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Domain\Tenancy\Models\Approval;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * draft|rejected -> pending_approval, opening an Approval (ADR-08, BR-613).
 *
 * The approval record is the source of truth for the decision; the expense's
 * own status mirrors it so lists need no join (BR-901).
 */
final class SubmitExpenseAction
{
    public function handle(Expense $expense, ?User $submitter = null): Expense
    {
        if (! $expense->status->isEditable()) {
            throw ExpenseTransitionException::notSubmittable($expense->title, $expense->status);
        }

        return DB::transaction(function () use ($expense, $submitter): Expense {
            // BR-904: at most one non-terminal approval per subject. Guarded
            // inside the transaction rather than by a partial unique index,
            // which MySQL cannot express.
            $open = Approval::query()
                ->where('approvable_type', ApprovableCatalog::EXPENSE)
                ->where('approvable_id', $expense->id)
                ->where('status', ApprovalStatus::Pending->value)
                ->lockForUpdate()
                ->exists();

            if ($open) {
                throw ExpenseTransitionException::alreadyAwaitingDecision($expense->title);
            }

            $expense->approvals()->create([
                'status' => ApprovalStatus::Pending,
                'level' => 1,
                'current_level' => 1,
                'requested_by' => $submitter?->id,
            ]);

            $expense->update([
                'status' => ExpenseStatus::PendingApproval,
                'submitted_by' => $submitter?->id ?? $expense->submitted_by,
                'rejection_reason' => null,
            ]);

            return $expense->refresh();
        });
    }
}
