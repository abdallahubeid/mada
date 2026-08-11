<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\JobPostingStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\LeaveRequest;
use Illuminate\Support\Collection;

/**
 * Builds Owner executive dashboard KPIs, chart series, and quick-action queues.
 */
class ExecutiveDashboard
{
    /**
     * @return array{
     *     kpis: array<string, int|float>,
     *     attendanceChart: array{labels: list<string>, present: list<int>, absent: list<int>},
     *     departmentChart: array{labels: list<string>, values: list<int>},
     *     pipeline: array{open_jobs: int, applications: int, resigned_90d: int},
     *     pendingLeaves: Collection<int, LeaveRequest>,
     *     expiringContracts: Collection<int, EmployeeContract>
     * }
     */
    public function build(): array
    {
        $monthStart = now()->startOfMonth()->toDateString();
        $monthEnd = now()->endOfMonth()->toDateString();

        $totalEmployees = Employee::query()->count();
        $activeContracts = EmployeeContract::query()
            ->where('status', ContractStatus::Active)
            ->count();
        $pendingLeaves = LeaveRequest::query()
            ->where('status', LeaveRequestStatus::Pending)
            ->count();

        $monthAttendance = Attendance::query()
            ->whereBetween('date', [$monthStart, $monthEnd])
            ->get(['status']);

        $presentLike = $monthAttendance->filter(
            fn (Attendance $row) => in_array($row->status, [
                AttendanceStatus::Present,
                AttendanceStatus::Late,
                AttendanceStatus::HalfDay,
            ], true)
        )->count();

        $attendanceRate = $monthAttendance->isEmpty()
            ? 0.0
            : round(($presentLike / $monthAttendance->count()) * 100, 1);

        return [
            'kpis' => [
                'total_employees' => $totalEmployees,
                'active_contracts' => $activeContracts,
                'pending_leaves' => $pendingLeaves,
                'attendance_rate' => $attendanceRate,
            ],
            'attendanceChart' => $this->attendanceSeries(),
            'departmentChart' => $this->departmentSeries(),
            'pipeline' => [
                'open_jobs' => JobPosting::query()
                    ->where('status', JobPostingStatus::Published)
                    ->count(),
                'applications' => JobApplication::query()->count(),
                'resigned_90d' => Employee::query()
                    ->where('status', EmployeeStatus::Resigned)
                    ->where('updated_at', '>=', now()->subDays(90))
                    ->count(),
            ],
            'pendingLeaves' => LeaveRequest::query()
                ->with(['employee', 'leaveType'])
                ->where('status', LeaveRequestStatus::Pending)
                ->latest()
                ->limit(5)
                ->get(),
            'expiringContracts' => EmployeeContract::query()
                ->with('employee')
                ->where('status', ContractStatus::Active)
                ->whereNotNull('end_date')
                ->whereBetween('end_date', [now()->toDateString(), now()->addDays(30)->toDateString()])
                ->orderBy('end_date')
                ->limit(5)
                ->get(),
        ];
    }

    /**
     * @return array{labels: list<string>, present: list<int>, absent: list<int>}
     */
    private function attendanceSeries(): array
    {
        $labels = [];
        $present = [];
        $absent = [];

        for ($i = 5; $i >= 0; $i--) {
            $month = now()->subMonths($i);
            $labels[] = $month->translatedFormat('M Y');

            $rows = Attendance::query()
                ->whereYear('date', $month->year)
                ->whereMonth('date', $month->month)
                ->get(['status']);

            $present[] = $rows->filter(
                fn (Attendance $row) => in_array($row->status, [
                    AttendanceStatus::Present,
                    AttendanceStatus::Late,
                    AttendanceStatus::HalfDay,
                ], true)
            )->count();

            $absent[] = $rows->filter(
                fn (Attendance $row) => $row->status === AttendanceStatus::Absent
            )->count();
        }

        return compact('labels', 'present', 'absent');
    }

    /**
     * @return array{labels: list<string>, values: list<int>}
     */
    private function departmentSeries(): array
    {
        $rows = Department::query()
            ->withCount('employees')
            ->orderByDesc('employees_count')
            ->limit(8)
            ->get();

        return [
            'labels' => $rows->pluck('name')->all(),
            'values' => $rows->pluck('employees_count')->map(fn ($n) => (int) $n)->all(),
        ];
    }
}
