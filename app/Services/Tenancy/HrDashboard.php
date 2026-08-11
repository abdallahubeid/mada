<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use App\Domain\Tenancy\Enums\EvaluationStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Enums\TaskStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Models\Task;
use Illuminate\Support\Collection;

/**
 * Builds the HR Manager dashboard: today's workforce state, the approval
 * queues HR acts on, and org-wide task/analytics rollups.
 *
 * Distinct from {@see ExecutiveDashboard} (Owner, financial/strategic framing)
 * and {@see EmployeeDashboard} (personal, self-service framing).
 */
class HrDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(): array
    {
        $today = now()->toDateString();
        $attendanceToday = $this->attendanceToday($today);

        return [
            'kpis' => [
                'headcount' => $attendanceToday['headcount'],
                'present_today' => $attendanceToday['present'],
                'absence_rate' => $attendanceToday['absence_rate'],
                'pending_leaves' => LeaveRequest::query()
                    ->where('status', LeaveRequestStatus::Pending)
                    ->count(),
            ],
            'attendanceToday' => $attendanceToday,
            'pendingLeaves' => LeaveRequest::query()
                ->with(['employee', 'leaveType'])
                ->where('status', LeaveRequestStatus::Pending)
                ->orderBy('start_date')
                ->limit(6)
                ->get(),
            'evaluations' => $this->evaluationProgress(),
            'tasks' => $this->taskRollup(),
            'expiringContracts' => EmployeeContract::query()
                ->with('employee')
                ->where('status', ContractStatus::Active)
                ->whereNotNull('end_date')
                ->whereDate('end_date', '>=', $today)
                ->whereDate('end_date', '<=', now()->addDays(30)->toDateString())
                ->orderBy('end_date')
                ->limit(6)
                ->get(),
            'endingProbations' => EmployeeContract::query()
                ->with('employee')
                ->where('status', ContractStatus::Active)
                ->whereNotNull('probation_end_date')
                ->whereDate('probation_end_date', '>=', $today)
                ->whereDate('probation_end_date', '<=', now()->addDays(30)->toDateString())
                ->orderBy('probation_end_date')
                ->limit(6)
                ->get(),
            'onLeaveToday' => $this->onLeaveToday($today),
            'anniversaries' => $this->upcomingAnniversaries(),
            'lateToday' => Attendance::query()
                ->with('employee')
                ->whereDate('date', $today)
                ->where('status', AttendanceStatus::Late)
                ->limit(6)
                ->get(),
        ];
    }

    /**
     * Today's attendance split.
     *
     * `AttendanceStatus::Absent` only exists when someone explicitly recorded it,
     * so a no-show with no row at all would be invisible to a naive status
     * count. `no_record` closes that gap: active headcount minus everyone who
     * has any row today. Absence rate counts both buckets, which is why it can
     * differ from what a status-only count would report.
     *
     * @return array<string, int|float>
     */
    private function attendanceToday(string $today): array
    {
        $headcount = Employee::query()
            ->whereIn('status', [EmployeeStatus::Active, EmployeeStatus::OnLeave])
            ->count();

        $rows = Attendance::query()
            ->whereDate('date', $today)
            ->get(['status', 'employee_id']);

        $counts = [
            'present' => $rows->where('status', AttendanceStatus::Present)->count(),
            'late' => $rows->where('status', AttendanceStatus::Late)->count(),
            'half_day' => $rows->where('status', AttendanceStatus::HalfDay)->count(),
            'absent' => $rows->where('status', AttendanceStatus::Absent)->count(),
        ];

        $noRecord = max(0, $headcount - $rows->unique('employee_id')->count());
        $missing = $counts['absent'] + $noRecord;

        return [
            ...$counts,
            'no_record' => $noRecord,
            'headcount' => $headcount,
            'absence_rate' => $headcount === 0
                ? 0.0
                : round(($missing / $headcount) * 100, 1),
        ];
    }

    /**
     * Evaluation completion for the tenant's configured period.
     *
     * @return array<string, mixed>
     */
    private function evaluationProgress(): array
    {
        $periodType = $this->defaultPeriodType();
        $periods = app(EvaluationPeriodCatalog::class);
        $periodKey = $periods->currentKey($periodType);

        $headcount = Employee::query()
            ->where('status', EmployeeStatus::Active)
            ->count();

        $rows = EmployeeEvaluation::query()
            ->where('period_type', $periodType)
            ->where('period_key', $periodKey)
            ->get(['status']);

        $approved = $rows->where('status', EvaluationStatus::Approved)->count();
        $submitted = $rows->where('status', EvaluationStatus::Submitted)->count();
        $draft = $rows->where('status', EvaluationStatus::Draft)->count();

        return [
            'period_label' => $periods->label($periodType, $periodKey),
            'period_type' => $periodType,
            'period_key' => $periodKey,
            'headcount' => $headcount,
            'approved' => $approved,
            'submitted' => $submitted,
            'draft' => $draft,
            'not_started' => max(0, $headcount - $rows->count()),
            'completion_rate' => $headcount === 0
                ? 0.0
                : round((($approved + $submitted) / $headcount) * 100, 1),
        ];
    }

    /**
     * Org-wide task rollup across the four Scrum columns, plus overdue.
     *
     * @return array<string, mixed>
     */
    private function taskRollup(): array
    {
        $rows = Task::query()->get(['status', 'due_date']);

        $byStatus = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => $rows->where('status', $status)->count(),
            ])
            ->all();

        $overdue = $rows
            ->filter(fn (Task $task): bool => $task->status !== TaskStatus::Completed
                && $task->due_date !== null
                && $task->due_date->isBefore(now()->startOfDay()))
            ->count();

        return [
            'by_status' => $byStatus,
            'total' => $rows->count(),
            'overdue' => $overdue,
        ];
    }

    /**
     * @return Collection<int, Employee>
     */
    private function onLeaveToday(string $today): Collection
    {
        $employeeIds = LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Approved)
            ->whereDate('start_date', '<=', $today)
            ->whereDate('end_date', '>=', $today)
            ->pluck('employee_id')
            ->unique();

        if ($employeeIds->isEmpty()) {
            return collect();
        }

        return Employee::query()
            ->whereIn('id', $employeeIds)
            ->orderBy('first_name')
            ->limit(8)
            ->get();
    }

    /**
     * Work anniversaries in the next 30 days, derived from `joining_date`.
     *
     * The employees table has no birth date, so this is the anniversary the
     * schema can actually support. Compared on month/day so the year the
     * employee joined is irrelevant.
     *
     * @return Collection<int, array{employee: Employee, years: int, date: string}>
     */
    private function upcomingAnniversaries(): Collection
    {
        $start = now()->startOfDay();
        $end = now()->addDays(30)->endOfDay();

        return Employee::query()
            ->where('status', EmployeeStatus::Active)
            ->whereNotNull('joining_date')
            ->get()
            ->map(function (Employee $employee) use ($start): ?array {
                $joined = $employee->joining_date;

                if ($joined === null) {
                    return null;
                }

                $next = $joined->copy()->year($start->year);

                if ($next->lessThan($start)) {
                    $next->addYear();
                }

                $years = $next->year - $joined->year;

                if ($years < 1) {
                    return null;
                }

                return [
                    'employee' => $employee,
                    'years' => $years,
                    'date' => $next->toDateString(),
                ];
            })
            ->filter()
            ->filter(fn (array $row): bool => $row['date'] <= $end->toDateString())
            ->sortBy('date')
            ->take(6)
            ->values();
    }

    private function defaultPeriodType(): EvaluationPeriodType
    {
        $value = OrgSetting::query()->first()?->evaluation_periodicity;

        if ($value instanceof EvaluationPeriodType) {
            return $value;
        }

        return EvaluationPeriodType::tryFrom((string) ($value ?? ''))
            ?? EvaluationPeriodType::Quarterly;
    }
}
