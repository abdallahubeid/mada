<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Exceptions\ExpenseTransitionException;
use App\Domain\Finance\Models\Expense;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * pending_approval -> approved, closing the Approval (BR-613).
 *
 * Separation of duties applies here too: the person who submitted a claim may
 * not approve their own. Unlike payroll this is mostly about self-reimbursement
 * — an employee approving the money they are owed — which is the single most
 * obvious abuse of an expense system.
 */
final class ApproveExpenseAction
{
    public function handle(Expense $expense, User $approver): Expense
    {
        if ($expense->status !== ExpenseStatus::PendingApproval) {
            throw ExpenseTransitionException::notAwaitingDecision($expense->title, $expense->status);
        }

        if ($expense->submitted_by !== null && (int) $expense->submitted_by === (int) $approver->id) {
            throw ExpenseTransitionException::submitterCannotDecide($expense->title);
        }

        return DB::transaction(function () use ($expense, $approver): Expense {
            $expense->currentApproval?->update([
                'status' => ApprovalStatus::Approved,
                'decided_by' => $approver->id,
                'decided_at' => now(),
            ]);

            $expense->update([
                'status' => ExpenseStatus::Approved,
                'decided_by' => $approver->id,
                'decided_at' => now(),
                'rejection_reason' => null,
            ]);

            return $expense->refresh();
        });
    }
}
