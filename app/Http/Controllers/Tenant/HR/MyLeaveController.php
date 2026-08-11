<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\LeaveRequestSubmitted;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

/**
 * Employee self-service leave — own balances, own history, own requests.
 *
 * Split out of the retired MySpaceController. Distinct from {@see LeaveController},
 * which is the HR-facing approval console; every query here is pinned to the
 * acting user's own employee profile.
 */
class MyLeaveController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $employee = $this->resolveEmployee($request);

        if ($employee === null) {
            return view('tenant.hr.employee.leaves', ['employee' => null]);
        }

        $leaveTypes = LeaveType::query()->orderBy('name')->get();

        $leaveBalances = $leaveTypes->map(function (LeaveType $type) use ($employee): array {
            $remaining = $type->remainingDaysFor($employee->id);

            return [
                'type' => $type,
                'annual' => $type->annual_days,
                'used' => max(0, $type->annual_days - $remaining),
                'remaining' => $remaining,
            ];
        });

        return view('tenant.hr.employee.leaves', [
            'employee' => $employee,
            'leaveTypes' => $leaveTypes,
            'leaveBalances' => $leaveBalances,
            'remainingLeaveDays' => (int) $leaveBalances->sum('remaining'),
            'leaveRequests' => LeaveRequest::query()
                ->with('leaveType')
                ->where('employee_id', $employee->id)
                ->latest()
                ->paginate(config('app.paginate_page')),
        ]);
    }

    public function store(Request $request): RedirectResponse
    {
        $employee = $this->requireEmployee($request);
        $tenantId = $this->tenantContext->getTenantId();

        $data = $request->validate([
            'leave_type_id' => [
                'required',
                'integer',
                Rule::exists('leave_types', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'start_date' => ['required', 'date'],
            'end_date' => ['required', 'date', 'after_or_equal:start_date'],
            'reason' => ['nullable', 'string', 'max:5000'],
        ]);

        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);
        $daysCount = LeaveRequest::calculateDaysCount($data['start_date'], $data['end_date']);

        if ($daysCount < 1) {
            flash()->error('المدة المحددة تقع بالكامل ضمن عطلات رسمية. اختر تواريخاً أخرى.');

            return redirect()->route('tenant.hr.my-leaves')->withInput();
        }

        $remaining = $leaveType->remainingDaysFor($employee->id);

        if ($daysCount > $remaining) {
            flash()->error("رصيد الإجازة غير كافٍ. المتبقي: {$remaining} يوم.");

            return redirect()->route('tenant.hr.my-leaves')->withInput();
        }

        $status = $leaveType->requires_approval
            ? LeaveRequestStatus::Pending
            : LeaveRequestStatus::Approved;

        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'days_count' => $daysCount,
            'reason' => $data['reason'] ?? null,
            'status' => $status,
            'approved_by' => $status === LeaveRequestStatus::Approved ? $request->user()?->id : null,
        ]);

        if ($status === LeaveRequestStatus::Approved && $leaveRequest->coversDate(now())) {
            if ($employee->status === EmployeeStatus::Active) {
                $employee->update(['status' => EmployeeStatus::OnLeave]);
            }
        }

        event(new LeaveRequestSubmitted($leaveRequest, $request->user()?->id));

        flash()->success('تم تقديم طلب الإجازة بنجاح.');

        return redirect()->route('tenant.hr.my-leaves');
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
