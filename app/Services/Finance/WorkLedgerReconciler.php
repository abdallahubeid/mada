<?php

namespace App\Services\Finance;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\WorkLedgerException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Enums\WorkLedgerDayType;
use App\Domain\Tenancy\Enums\WorkLedgerSource;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\OfficialHoliday;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\Models\WorkLedgerEntry;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Writes the materialized Work Ledger (ADR-21, BR-402/BR-403).
 *
 * The ONE place the absence calculation lives. Reconciliation resolves each
 * employee-date into exactly one mutually-exclusive classification, in
 * precedence order:
 *
 *   holiday > weekend > excused > present > absent
 *
 * Rebuilds are idempotent: the period is hard-deleted and re-inserted inside a
 * transaction, so running twice produces identical rows (BR-406). That is safe
 * precisely because a locked payroll run has already snapshotted the numbers it
 * needed (BR-608) — and a period covered by one refuses to rebuild (BR-407).
 */
final class WorkLedgerReconciler
{
    /**
     * Rebuild a date range for every active employee.
     *
     * @return int rows written
     *
     * @throws WorkLedgerException
     */
    public function reconcilePeriod(Carbon $start, Carbon $end): int
    {
        $employeeIds = Employee::query()
            ->where('status', EmployeeStatus::Active->value)
            ->pluck('id')
            ->map(intval(...))
            ->all();

        return $this->reconcile($employeeIds, $start, $end);
    }

    /**
     * Rebuild a date range for the given employees.
     *
     * @param  list<int>  $employeeIds
     * @return int rows written
     *
     * @throws WorkLedgerException
     */
    public function reconcile(array $employeeIds, Carbon $start, Carbon $end): int
    {
        $start = $start->copy()->startOfDay();
        $end = $end->copy()->startOfDay();

        if ($end->lt($start)) {
            throw WorkLedgerException::invalidRange($start, $end);
        }

        $this->guardNoLockedRun($start, $end);

        if ($employeeIds === []) {
            return 0;
        }

        $calendar = WorkCalendar::query()->first();
        $weekendDays = $calendar?->resolvedWeekendDays() ?? [5, 6];
        $holidays = OfficialHoliday::query()->get();

        $attendance = $this->attendanceByEmployeeDate($employeeIds, $start, $end);
        $leave = $this->approvedLeaveByEmployeeDate($employeeIds, $start, $end);

        return DB::transaction(function () use ($employeeIds, $start, $end, $weekendDays, $holidays, $attendance, $leave): int {
            // Hard delete, not soft: a derived projection carries no history
            // of its own, and a trashed row would collide with the
            // (tenant_id, employee_id, date) unique key on the next rebuild.
            WorkLedgerEntry::query()
                ->whereIn('employee_id', $employeeIds)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->delete();

            $written = 0;

            foreach ($employeeIds as $employeeId) {
                foreach ($this->datesIn($start, $end) as $date) {
                    $classification = $this->classify(
                        $date,
                        $weekendDays,
                        $holidays,
                        $attendance[$employeeId][$date->toDateString()] ?? null,
                        $leave[$employeeId][$date->toDateString()] ?? null,
                    );

                    WorkLedgerEntry::create([
                        'employee_id' => $employeeId,
                        'date' => $date->toDateString(),
                        ...$classification,
                    ]);

                    $written++;
                }
            }

            return $written;
        });
    }

    /**
     * Resolve one employee-date into exactly one day type (BR-403).
     *
     * @param  Collection<int, OfficialHoliday>  $holidays
     * @param  list<int>  $weekendDays
     * @return array<string, mixed>
     */
    private function classify(
        Carbon $date,
        array $weekendDays,
        Collection $holidays,
        ?Attendance $attendance,
        ?LeaveRequest $leave,
    ): array {
        if (OfficialHoliday::isHoliday($date, $holidays)) {
            return [
                'day_type' => WorkLedgerDayType::Holiday,
                'source' => WorkLedgerSource::OfficialHoliday,
            ];
        }

        if (in_array($date->dayOfWeek, $weekendDays, true)) {
            return [
                'day_type' => WorkLedgerDayType::Weekend,
                'source' => WorkLedgerSource::WorkCalendar,
            ];
        }

        // Approved leave outranks attendance: an employee who checked in on an
        // approved leave day must not also be marked absent later, and must
        // never be deducted for it (BR-401, ADR-06).
        if ($leave !== null) {
            return [
                'day_type' => WorkLedgerDayType::Excused,
                'source' => WorkLedgerSource::LeaveRequest,
                'leave_request_id' => $leave->id,
            ];
        }

        if ($attendance !== null && $attendance->status !== AttendanceStatus::Absent) {
            return [
                'day_type' => WorkLedgerDayType::Present,
                'source' => WorkLedgerSource::Attendance,
                'attendance_id' => $attendance->id,
                'worked_minutes' => $this->workedMinutes($attendance),
            ];
        }

        // A scheduled working day with no leave and no attendance — or an
        // explicitly recorded absence. Both deduct (BR-402/BR-404).
        return [
            'day_type' => WorkLedgerDayType::Absent,
            'source' => $attendance !== null
                ? WorkLedgerSource::Attendance
                : WorkLedgerSource::WorkCalendar,
            'attendance_id' => $attendance?->id,
        ];
    }

    private function workedMinutes(Attendance $attendance): ?int
    {
        if ($attendance->check_in === null || $attendance->check_out === null) {
            return null;
        }

        return (int) abs($attendance->check_in->diffInMinutes($attendance->check_out));
    }

    /**
     * @return list<Carbon>
     */
    private function datesIn(Carbon $start, Carbon $end): array
    {
        $dates = [];

        for ($date = $start->copy(); $date->lte($end); $date->addDay()) {
            $dates[] = $date->copy();
        }

        return $dates;
    }

    /**
     * @param  list<int>  $employeeIds
     * @return array<int, array<string, Attendance>>
     */
    private function attendanceByEmployeeDate(array $employeeIds, Carbon $start, Carbon $end): array
    {
        $indexed = [];

        Attendance::query()
            ->whereIn('employee_id', $employeeIds)
            ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
            ->get()
            ->each(function (Attendance $row) use (&$indexed): void {
                $indexed[(int) $row->employee_id][$row->date->toDateString()] = $row;
            });

        return $indexed;
    }

    /**
     * Approved leave expanded to one entry per covered date.
     *
     * @param  list<int>  $employeeIds
     * @return array<int, array<string, LeaveRequest>>
     */
    private function approvedLeaveByEmployeeDate(array $employeeIds, Carbon $start, Carbon $end): array
    {
        $indexed = [];

        LeaveRequest::query()
            ->whereIn('employee_id', $employeeIds)
            ->where('status', LeaveRequestStatus::Approved->value)
            ->whereDate('start_date', '<=', $end->toDateString())
            ->whereDate('end_date', '>=', $start->toDateString())
            ->get()
            ->each(function (LeaveRequest $leave) use (&$indexed, $start, $end): void {
                $from = $leave->start_date->copy()->startOfDay()->max($start);
                $to = $leave->end_date->copy()->startOfDay()->min($end);

                for ($date = $from->copy(); $date->lte($to); $date->addDay()) {
                    $indexed[(int) $leave->employee_id][$date->toDateString()] ??= $leave;
                }
            });

        return $indexed;
    }

    /**
     * BR-407: a period covered by an approved or paid run is frozen.
     */
    private function guardNoLockedRun(Carbon $start, Carbon $end): void
    {
        $locked = PayrollRun::query()
            ->whereIn('status', [PayrollRunStatus::Approved->value, PayrollRunStatus::Paid->value])
            ->whereDate('period_start', '<=', $end->toDateString())
            ->whereDate('period_end', '>=', $start->toDateString())
            ->first();

        if ($locked !== null) {
            throw WorkLedgerException::periodLockedByRun($locked->period);
        }
    }
}
