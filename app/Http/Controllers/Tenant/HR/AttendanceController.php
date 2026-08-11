<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\AttendanceMarkedLate;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreAttendanceRequest;
use App\Http\Requests\Tenant\UpdateAttendanceRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class AttendanceController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $date = $request->filled('date')
            ? Carbon::parse((string) $request->string('date'))->toDateString()
            : now()->toDateString();

        $logs = Attendance::query()
            ->with('employee.department')
            ->whereDate('date', $date)
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->when(
                $request->filled('employee_id') && $request->string('employee_id') !== 'all',
                fn ($query) => $query->where('employee_id', (int) $request->integer('employee_id')),
            )
            ->orderByDesc('check_in')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        $employees = Employee::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();

        $todayByEmployee = Attendance::query()
            ->whereDate('date', $date)
            ->get()
            ->keyBy('employee_id');

        return view('tenant.hr.attendance.index', [
            'logs' => $logs,
            'employees' => $employees,
            'todayByEmployee' => $todayByEmployee,
            'statuses' => AttendanceStatus::cases(),
            'filters' => [
                'date' => $date,
                'status' => (string) $request->string('status', 'all'),
                'employee_id' => (string) $request->string('employee_id', 'all'),
            ],
        ]);
    }

    public function store(StoreAttendanceRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $date = $data['date'] ?? now()->toDateString();
        $checkIn = filled($data['check_in'] ?? null)
            ? Carbon::parse("{$date} {$data['check_in']}")
            : now();

        $status = isset($data['status'])
            ? AttendanceStatus::from($data['status'])
            : Attendance::resolveCheckInStatus($checkIn);

        $attendance = Attendance::query()->updateOrCreate(
            [
                'employee_id' => $data['employee_id'],
                'date' => $date,
            ],
            [
                'check_in' => $checkIn,
                'check_out' => filled($data['check_out'] ?? null)
                    ? Carbon::parse("{$date} {$data['check_out']}")
                    : null,
                'status' => $status,
                'notes' => $data['notes'] ?? null,
            ],
        );

        if ($status === AttendanceStatus::Late) {
            event(new AttendanceMarkedLate($attendance->fresh()));
        }

        flash()->success(
            $attendance->wasRecentlyCreated
                ? 'تم تسجيل الحضور بنجاح.'
                : 'تم تحديث سجل الحضور بنجاح.'
        );

        return redirect()->back();
    }

    public function update(UpdateAttendanceRequest $request, Attendance $attendance): RedirectResponse
    {
        $this->ensureTenantAttendance($attendance);

        $data = $request->validated();
        $date = ($data['date'] ?? $attendance->date?->toDateString()) ?: now()->toDateString();

        $payload = [
            'notes' => $data['notes'] ?? $attendance->notes,
        ];

        if (array_key_exists('status', $data) && filled($data['status'])) {
            $payload['status'] = AttendanceStatus::from($data['status']);
        }

        if (filled($data['check_in'] ?? null)) {
            $payload['check_in'] = Carbon::parse("{$date} {$data['check_in']}");
        }

        if (array_key_exists('check_out', $data)) {
            $payload['check_out'] = filled($data['check_out'])
                ? Carbon::parse("{$date} {$data['check_out']}")
                : null;
        }

        if (($data['action'] ?? null) === 'check_out' && $attendance->check_out === null) {
            $payload['check_out'] = now();

            if ($attendance->check_in !== null) {
                $workedMinutes = $attendance->check_in->diffInMinutes(now());
                if ($workedMinutes < 240 && ! isset($payload['status'])) {
                    $payload['status'] = AttendanceStatus::HalfDay;
                }
            }
        }

        $attendance->update($payload);

        flash()->info('تم تحديث سجل الحضور.');

        return redirect()->back();
    }

    public function checkIn(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:2000'],
        ]);

        $employee = Employee::query()->findOrFail($validated['employee_id']);
        $this->ensureTenantEmployee($employee);

        $today = now()->toDateString();
        $existing = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', $today)
            ->first();

        if ($existing?->check_in !== null) {
            flash()->error('تم تسجيل حضور هذا الموظف اليوم مسبقاً.');

            return redirect()->back();
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
                'notes' => $validated['notes'] ?? $existing?->notes,
            ],
        );

        if ($status === AttendanceStatus::Late) {
            event(new AttendanceMarkedLate($attendance->fresh()));
        }

        flash()->success('تم تسجيل الحضور بنجاح.');

        return redirect()->back();
    }

    public function checkOut(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
        ]);

        $employee = Employee::query()->findOrFail($validated['employee_id']);
        $this->ensureTenantEmployee($employee);

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

        if ($attendance === null || $attendance->check_in === null) {
            flash()->error('لا يوجد حضور مسجّل اليوم لهذا الموظف.');

            return redirect()->back();
        }

        if ($attendance->check_out !== null) {
            flash()->error('تم تسجيل الانصراف مسبقاً.');

            return redirect()->back();
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

        flash()->success('تم تسجيل الانصراف بنجاح.');

        return redirect()->back();
    }

    public function checkOutSelf(Request $request): RedirectResponse
    {
        $employee = $request->user()?->employee;

        abort_if($employee === null, 403, 'لا يوجد ملف موظف مرتبط بحسابك.');

        $attendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->whereNull('check_out')
            ->first();

        if ($attendance === null) {
            flash()->error('لا يوجد حضور مسجّل لك اليوم بانتظار تسجيل الانصراف.');

            return redirect()->back();
        }

        $attendance->update(['check_out' => now()->toTimeString()]);

        flash()->success('تم تسجيل انصرافك بنجاح.');

        return redirect()->back();
    }

    private function ensureTenantAttendance(Attendance $attendance): void
    {
        abort_unless(
            (int) $attendance->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }

    private function ensureTenantEmployee(Employee $employee): void
    {
        abort_unless(
            (int) $employee->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
