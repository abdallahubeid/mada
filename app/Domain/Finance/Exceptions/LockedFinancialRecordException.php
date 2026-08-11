<?php

namespace App\Domain\Finance\Exceptions;

use RuntimeException;

/**
 * An attempt to mutate a locked financial record was rejected (BR-610, NFR-11).
 *
 * Thrown from model observers rather than controllers or policies, so it fires
 * on every write path — including one added years from now by someone who has
 * never read BR-603. Corrections to a locked run go through
 * `payroll_run_adjustments` in a subsequent run, never an edit.
 */
class LockedFinancialRecordException extends RuntimeException
{
    public static function forRun(string $period, string $operation): self
    {
        return new self(
            "Payroll run {$period} is locked; {$operation} is not permitted. "
            .'Corrections require an adjustment entry in a subsequent run (BR-603).'
        );
    }

    public static function forPayslip(int $payslipId, string $operation): self
    {
        return new self(
            "Payslip #{$payslipId} belongs to a locked payroll run; {$operation} is not permitted (BR-610)."
        );
    }

    public static function forLineItem(int $lineItemId, string $operation): self
    {
        return new self(
            "Payslip line item #{$lineItemId} belongs to a locked payroll run; {$operation} is not permitted (BR-610)."
        );
    }
}
