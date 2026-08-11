<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\AttendanceMarkedLate;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Employee self-service attendance — own check-in/out and own history.
 *
 * Split out of the retired MySpaceController, whose tabbed page was replaced by
 * first-class self-service routes. Distinct from {@see AttendanceController},
 * which is the HR-facing console acting on *any* employee's record; every query
 * here is pinned to the acting user's own employee profile.
 */
class MyAttendanceController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);

        if ($employee === null) {
            return view('tenant.hr.employee.attendance', ['employee' => null]);
        }

        $employee->loadMissing(['department', 'manager']);

        $monthRows = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereYear('date', now()->year)
            ->whereMonth('date', now()->month)
            ->get(['status', 'check_in']);

        return view('tenant.hr.employee.attendance', [
            'employee' => $employee,
            'todayAttendance' => Attendance::query()
                ->where('employee_id', $employee->id)
                ->whereDate('date', now()->toDateString())
                ->first(),
            'monthSummary' => [
                'present' => $monthRows->where('status', AttendanceStatus::Present)->count(),
                'late' => $monthRows->where('status', AttendanceStatus::Late)->count(),
                'half_day' => $monthRows->where('status', AttendanceStatus::HalfDay)->count(),
                'absent' => $monthRows->where('status', AttendanceStatus::Absent)->count(),
                'recorded' => $monthRows->whereNotNull('check_in')->count(),
            ],
            'attendances' => Attendance::query()
                ->where('employee_id', $employee->id)
                ->orderByDesc('date')
                ->paginate(config('app.paginate_page')),
        ]);
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $employee = $this->requireEmployee($request);

        $today = now()->toDateString();
        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing?->check_in !== null) {
            flash()->error('تم تسجيل حضورك اليوم مسبقاً.');

            return redirect()->route('tenant.hr.my-attendance');
        }

        $checkIn = now();
        $status = Attendance::resolveCheckInStatus($checkIn);

        $attendance = Attendance::query()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'date' => $today,
            ],
            [
                'check_in' => $checkIn,
                'status' => $status,
            ],
        );

        if ($status === AttendanceStatus::Late) {
            event(new AttendanceMarkedLate($attendance->fresh()));
        }

        flash()->success('تم تسجيل حضورك بنجاح.');

        return redirect()->route('tenant.hr.my-attendance');
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $employee = $this->requireEmployee($request);

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if ($attendance === null || $attendance->check_in === null) {
            flash()->error('لا يوجد حضور مسجّل لك اليوم.');

            return redirect()->route('tenant.hr.my-attendance');
        }

        if ($attendance->check_out !== null) {
            flash()->error('تم تسجيل انصرافك مسبقاً.');

            return redirect()->route('tenant.hr.my-attendance');
        }

        $checkOut = now();
        $workedMinutes = $attendance->check_in->diffInMinutes($checkOut);
        $status = $workedMinutes < 240 && $attendance->status !== AttendanceStatus::Late
            ? AttendanceStatus::HalfDay
            : $attendance->status;

        $attendance->update([
            'check_out' => $checkOut,
            'status' => $status,
        ]);

        flash()->success('تم تسجيل انصرافك بنجاح.');

        return redirect()->route('tenant.hr.my-attendance');
    }

    private function resolveEmployee(Request $request): ?Employee
    {
        $employee = $request->user()?->employee;

        if ($employee === null) {
            return null;
        }

        abort_unless(
            (int) $employee->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );

        return $employee;
    }

    private function requireEmployee(Request $request): Employee
    {
        $employee = $this->resolveEmployee($request);

        abort_if($employee === null, 403, 'لا يوجد ملف موظف مرتبط بحسابك.');

        return $employee;
    }
}
