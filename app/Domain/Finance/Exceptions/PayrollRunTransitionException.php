<?php

namespace App\Domain\Finance\Exceptions;

use App\Domain\Finance\Enums\PayrollRunStatus;
use RuntimeException;

/**
 * An illegal payroll run state transition was refused (BR-603, ADR-09).
 */
class PayrollRunTransitionException extends RuntimeException
{
    public static function notInState(
        string $period,
        string $operation,
        PayrollRunStatus $expected,
        PayrollRunStatus $actual,
    ): self {
        return new self(
            "Payroll run {$period} cannot accept {$operation}: expected status '{$expected->value}', found '{$actual->value}'."
        );
    }

    public static function hasNoPayslips(string $period): self
    {
        return new self("Payroll run {$period} has no payslips to approve.");
    }

    public static function makerCannotApprove(string $period): self
    {
        return new self(
            "The user who prepared payroll run {$period} may not also approve it (BR-615, ADR-09)."
        );
    }

    public static function rejectionNeedsReason(string $period): self
    {
        return new self("Rejecting payroll run {$period} requires a reason (BR-905).");
    }
}
