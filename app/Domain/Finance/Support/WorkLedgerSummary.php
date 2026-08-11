<?php

namespace App\Domain\Finance\Support;

/**
 * One employee's reconciled Work Ledger counts for a payroll period (BR-602).
 *
 * `periodScheduledDays` is the organisation's working-day count for the whole
 * period; `scheduledDays` is this employee's own count. They differ for
 * mid-period joiners and leavers, which is what makes proration fall out of the
 * ledger rather than needing a separate calendar (BR-605).
 */
final readonly class WorkLedgerSummary
{
    public function __construct(
        public int $periodScheduledDays,
        public int $scheduledDays,
        public int $presentDays,
        public int $excusedDays,
        public int $absentDays,
        public int $workedMinutes = 0,
        public int $unresolvedDays = 0,
    ) {}

    /**
     * BR-405: a period containing any `workday` sentinel row is not payable.
     */
    public function isFullyResolved(): bool
    {
        return $this->unresolvedDays === 0;
    }

    public static function empty(int $periodScheduledDays = 0): self
    {
        return new self(
            periodScheduledDays: $periodScheduledDays,
            scheduledDays: 0,
            presentDays: 0,
            excusedDays: 0,
            absentDays: 0,
        );
    }
}
