<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\EvaluationStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Enums\TaskStatus;
use App\Domain\Tenancy\Models\Announcement;
use App\Domain\Tenancy\Models\AssetAssignment;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\Task;
use Illuminate\Support\Collection;

/**
 * Builds the employee self-service dashboard — a glanceable landing page.
 *
 * Deliberately a summary, not a second My Space: every section links into the
 * existing deep-dive surfaces (My Space tabs, the Scrum board, My Evaluations)
 * rather than duplicating their history tables.
 *
 * Every query is filtered to the passed employee's own records.
 */
class EmployeeDashboard
{
    /**
     * @return array<string, mixed>
     */
    public function build(?Employee $employee): array
    {
        if ($employee === null) {
            return ['employee' => null];
        }

        $employee->loadMissing(['department', 'manager']);

        $leaveBalances = $this->leaveBalances($employee);

        return [
            'employee' => $employee,
            'todayAttendance' => Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', now()->toDateString())
                ->first(),
            'tasks' => $this->taskSummary($employee),
            'leaveBalances' => $leaveBalances,
            'remainingLeaveDays' => (int) $leaveBalances->sum('remaining'),
            'pendingLeaves' => LeaveRequest::query()
                ->with('leaveType')
                ->where('employee_id', $employee->id)
                ->where('status', LeaveRequestStatus::Pending)
                ->orderBy('start_date')
                ->limit(4)
                ->get(),
            'latestEvaluation' => $this->latestEvaluation($employee),
            'monthAttendance' => $this->monthAttendance($employee),
            'activeContract' => EmployeeContract::query()
                ->where('employee_id', $employee->id)
                ->where('status', ContractStatus::Active)
                ->orderByDesc('start_date')
                ->first(),
            'myAssets' => AssetAssignment::query()
                ->with('asset')
                ->where('employee_id', $employee->id)
                ->whereNull('returned_at')
                ->latest('assigned_at')
                ->limit(5)
                ->get(),
            'announcements' => Announcement::query()
                ->active()
                ->orderByDesc('is_pinned')
                ->orderByDesc('published_at')
                ->limit(4)
                ->get(),
        ];
    }

    /**
     * Counts per Scrum column, plus overdue and the next task due.
     *
     * @return array<string, mixed>
     */
    private function taskSummary(Employee $employee): array
    {
        $tasks = Task::query()
            ->where('employee_id', $employee->id)
            ->get();

        $byStatus = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => $tasks->where('status', $status)->count(),
            ])
            ->all();

        $open = $tasks->filter(fn (Task $task): bool => $task->status !== TaskStatus::Completed);

        return [
            'by_status' => $byStatus,
            'total' => $tasks->count(),
            'open' => $open->count(),
            'overdue' => $open
                ->filter(fn (Task $task): bool => $task->due_date !== null
                    && $task->due_date->isBefore(now()->startOfDay()))
                ->count(),
            'next_due' => $open
                ->filter(fn (Task $task): bool => $task->due_date !== null)
                ->sortBy('due_date')
                ->first(),
        ];
    }

    /**
     * @return Collection<int, array{type: LeaveType, annual: int, used: int, remaining: int}>
     */
    private function leaveBalances(Employee $employee): Collection
    {
        return LeaveType::query()
            ->orderBy('name')
            ->get()
            ->map(function (LeaveType $type) use ($employee): array {
                $remaining = $type->remainingDaysFor($employee->id);

                return [
                    'type' => $type,
                    'annual' => $type->annual_days,
                    'used' => max(0, $type->annual_days - $remaining),
                    'remaining' => $remaining,
                ];
            });
    }

    /**
     * Only Submitted/Approved are visible to the employee — a Draft is the
     * evaluator's private working copy, matching what My Evaluations shows.
     *
     * @return array{evaluation: EmployeeEvaluation, period_label: string}|null
     */
    private function latestEvaluation(Employee $employee): ?array
    {
        $evaluation = EmployeeEvaluation::query()
            ->with('evaluator')
            ->where('employee_id', $employee->id)
            ->whereIn('status', [EvaluationStatus::Submitted, EvaluationStatus::Approved])
            ->orderByDesc('period_key')
            ->first();

        if ($evaluation === null) {
            return null;
        }

        return [
            'evaluation' => $evaluation,
            'period_label' => app(EvaluationPeriodCatalog::class)->label(
                $evaluation->period_type,
                $evaluation->period_key,
            ),
        ];
    }

    /**
     * @return array<string, int>
     */
    private function monthAttendance(Employee $employee): array
    {
        $rows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->get(['status']);

        return [
            'present' => $rows->where('status', AttendanceStatus::Present)->count(),
            'late' => $rows->where('status', AttendanceStatus::Late)->count(),
            'absent' => $rows->where('status', AttendanceStatus::Absent)->count(),
            'half_day' => $rows->where('status', AttendanceStatus::HalfDay)->count(),
            'total' => $rows->count(),
        ];
    }
}
