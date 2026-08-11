<?php

namespace App\Domain\Finance\Exceptions;

use RuntimeException;

/**
 * An attempted correction to a locked payroll run was refused (BR-603).
 */
class PayrollAdjustmentException extends RuntimeException
{
    public static function targetRunNotDraft(string $period): self
    {
        return new self(
            "Adjustments can only be carried by a DRAFT run; {$period} is not a draft."
        );
    }

    public static function originalNotLocked(): self
    {
        return new self(
            'Only a locked (approved or paid) payslip needs an adjustment — an editable run should be corrected directly.'
        );
    }

    public static function cannotAdjustSelf(string $period): self
    {
        return new self(
            "A payroll run cannot adjust itself ({$period}). Corrections belong to a subsequent run (BR-603)."
        );
    }

    public static function zeroAmount(): self
    {
        return new self('An adjustment of zero moves no money and would only add noise to the payslip.');
    }

    public static function reasonRequired(): self
    {
        return new self('An adjustment requires a reason — it is a permanent financial record.');
    }

    public static function employeeNotInTargetRun(string $employeeName, string $period): self
    {
        return new self(
            "{$employeeName} has no payslip on run {$period}, so the adjustment has nowhere to be paid. "
            .'Carry it on a run that includes this employee.'
        );
    }
}
