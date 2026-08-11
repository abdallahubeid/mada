<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Exceptions\ExpenseTransitionException;
use App\Domain\Finance\Models\Expense;

/**
 * approved -> paid (BR-613).
 *
 * Only a CLAIMABLE expense can be disbursed. A non-claimable cost was settled
 * directly by the company and is owed to nobody — marking it "paid" would imply
 * a reimbursement that never happened.
 */
final class DisburseExpenseAction
{
    public function handle(Expense $expense): Expense
    {
        if ($expense->status !== ExpenseStatus::Approved) {
            throw ExpenseTransitionException::notApproved($expense->title, $expense->status);
        }

        if (! $expense->is_claimable) {
            throw ExpenseTransitionException::notClaimable($expense->title);
        }

        $expense->update([
            'status' => ExpenseStatus::Paid,
            'paid_at' => now(),
        ]);

        return $expense->refresh();
    }
}
