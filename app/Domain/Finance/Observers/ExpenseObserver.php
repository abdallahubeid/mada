<?php

namespace App\Domain\Finance\Observers;

use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Exceptions\ExpenseTransitionException;
use App\Domain\Finance\Models\Expense;

/**
 * Approved and paid expenses are financial records (BR-613, NFR-10/11).
 *
 * The transitions the workflow itself performs are permitted: approving,
 * rejecting and disbursing all write to a record that is (or is becoming)
 * locked. Everything else — editing the amount, changing the category,
 * deleting — is refused once a decision has been made.
 */
class ExpenseObserver
{
    /**
     * Fields the approve/disburse actions legitimately write.
     *
     * @var list<string>
     */
    private const TRANSITION_FIELDS = [
        'status', 'decided_by', 'decided_at', 'rejection_reason', 'paid_at', 'updated_at',
    ];

    public function updating(Expense $expense): void
    {
        $original = ExpenseStatus::tryFrom((string) $expense->getRawOriginal('status'));

        if ($original === null || ! $original->isLocked()) {
            return;
        }

        $changed = array_keys($expense->getDirty());

        if (array_diff($changed, self::TRANSITION_FIELDS) === []) {
            return;
        }

        throw ExpenseTransitionException::lockedRecord($expense->title);
    }

    public function deleting(Expense $expense): void
    {
        if ($expense->status->isLocked()) {
            throw ExpenseTransitionException::lockedRecord($expense->title);
        }
    }

    public function forceDeleting(Expense $expense): void
    {
        if ($expense->status->isLocked()) {
            throw ExpenseTransitionException::lockedRecord($expense->title);
        }
    }
}
