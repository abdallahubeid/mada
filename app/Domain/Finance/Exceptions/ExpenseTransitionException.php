<?php

namespace App\Domain\Finance\Exceptions;

use App\Domain\Finance\Enums\ExpenseStatus;
use RuntimeException;

/**
 * An illegal expense claim transition was refused (BR-613).
 */
class ExpenseTransitionException extends RuntimeException
{
    public static function notSubmittable(string $title, ExpenseStatus $status): self
    {
        return new self("Expense '{$title}' cannot be submitted from status '{$status->value}'.");
    }

    public static function alreadyAwaitingDecision(string $title): self
    {
        return new self("Expense '{$title}' already has an open approval (BR-904).");
    }

    public static function notAwaitingDecision(string $title, ExpenseStatus $status): self
    {
        return new self("Expense '{$title}' is '{$status->value}', not awaiting a decision.");
    }

    public static function submitterCannotDecide(string $title): self
    {
        return new self(
            "The user who submitted expense '{$title}' may not decide it — an employee approving their own reimbursement is the primary abuse an expense workflow exists to prevent."
        );
    }

    public static function rejectionNeedsReason(string $title): self
    {
        return new self("Rejecting expense '{$title}' requires a reason (BR-905).");
    }

    public static function notApproved(string $title, ExpenseStatus $status): self
    {
        return new self("Expense '{$title}' is '{$status->value}'; only an approved claim can be disbursed.");
    }

    public static function notClaimable(string $title): self
    {
        return new self(
            "Expense '{$title}' is not claimable — it was settled directly and is owed to nobody, so it cannot be disbursed."
        );
    }

    public static function lockedRecord(string $title): self
    {
        return new self("Expense '{$title}' is approved or paid and can no longer be edited or deleted.");
    }
}
