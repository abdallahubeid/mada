<?php

namespace App\Domain\Finance\Exceptions;

use Illuminate\Support\Carbon;
use RuntimeException;

/**
 * The Work Ledger refused a rebuild (ADR-21).
 */
class WorkLedgerException extends RuntimeException
{
    public static function invalidRange(Carbon $start, Carbon $end): self
    {
        return new self(
            "Work Ledger range end ({$end->toDateString()}) precedes its start ({$start->toDateString()})."
        );
    }

    public static function periodLockedByRun(string $period): self
    {
        return new self(
            "Work Ledger cannot be rebuilt: payroll run {$period} is approved or paid and has frozen this period (BR-407)."
        );
    }
}
