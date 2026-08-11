<?php

namespace App\Services\Finance;

use App\Domain\Finance\Support\WorkLedgerSummary;
use App\Domain\Tenancy\Enums\WorkLedgerDayType;
use App\Domain\Tenancy\Models\WorkLedgerEntry;
use Illuminate\Support\Carbon;

/**
 * Reads the materialized Work Ledger and rolls it up per employee for a
 * period (ADR-21).
 *
 * Single responsibility: querying and aggregating ledger rows. It computes no
 * money — {@see PayslipCalculator} does that, from the summaries this returns.
 */
final class WorkLedgerSummarizer
{
    /**
     * Day types that represent a scheduled working day, in any resolution
     * state. Weekends and holidays are excluded — they are never payable days.
     *
     * @var list<WorkLedgerDayType>
     */
    private const SCHEDULED_DAY_TYPES = [
        WorkLedgerDayType::Workday,
        WorkLedgerDayType::Present,
        WorkLedgerDayType::Excused,
        WorkLedgerDayType::Absent,
    ];

    /**
     * @param  list<int>  $employeeIds
     * @return array<int, WorkLedgerSummary> keyed by employee id
     */
    public function summarize(array $employeeIds, Carbon $start, Carbon $end): array
    {
        $periodScheduledDays = $this->periodScheduledDays($start, $end);

        if ($employeeIds === []) {
            return [];
        }

        $rows = WorkLedgerEntry::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->selectRaw('employee_id, day_type, count(*) as day_count, coalesce(sum(worked_minutes), 0) as minutes')
            ->groupBy('employee_id', 'day_type')
            ->get();

        /** @var array<int, array<string, int>> $counts */
        $counts = [];
        /** @var array<int, int> $minutes */
        $minutes = [];

        foreach ($rows as $row) {
            $employeeId = (int) $row->employee_id;
            $counts[$employeeId][(string) $row->day_type->value] = (int) $row->day_count;
            $minutes[$employeeId] = ($minutes[$employeeId] ?? 0) + (int) $row->minutes;
        }

        $summaries = [];

        foreach ($employeeIds as $employeeId) {
            $byType = $counts[$employeeId] ?? [];

            $scheduled = 0;
            foreach (self::SCHEDULED_DAY_TYPES as $dayType) {
                $scheduled += $byType[$dayType->value] ?? 0;
            }

            $summaries[$employeeId] = new WorkLedgerSummary(
                periodScheduledDays: $periodScheduledDays,
                scheduledDays: $scheduled,
                presentDays: $byType[WorkLedgerDayType::Present->value] ?? 0,
                excusedDays: $byType[WorkLedgerDayType::Excused->value] ?? 0,
                absentDays: $byType[WorkLedgerDayType::Absent->value] ?? 0,
                workedMinutes: $minutes[$employeeId] ?? 0,
                unresolvedDays: $byType[WorkLedgerDayType::Workday->value] ?? 0,
            );
        }

        return $summaries;
    }

    /**
     * The organisation's working-day count for the period.
     *
     * Derived from the ledger itself rather than re-reading the work calendar,
     * so proration and deduction share one basis with the reconciliation that
     * produced them.
     */
    public function periodScheduledDays(Carbon $start, Carbon $end): int
    {
        return WorkLedgerEntry::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->whereIn('day_type', array_map(
                static fn (WorkLedgerDayType $dayType): string => $dayType->value,
                self::SCHEDULED_DAY_TYPES,
            ))
            ->distinct()
            ->count('date');
    }

    /**
     * Unresolved `workday` sentinel rows blocking payment (BR-405).
     */
    public function unresolvedDayCount(Carbon $start, Carbon $end): int
    {
        return WorkLedgerEntry::query()
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->where('day_type', WorkLedgerDayType::Workday->value)
            ->count();
    }
}
