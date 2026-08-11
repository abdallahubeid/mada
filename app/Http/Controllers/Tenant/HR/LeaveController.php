<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\LeaveRequestDecided;
use App\Events\Tenancy\LeaveRequestSubmitted;
use App\Http\Controllers\Controller;
use App\Services\Tenancy\TenantAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class LeaveController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditor $auditor,
    ) {}

    public function index(Request $request): View
    {
        $requests = LeaveRequest::query()
            ->with(['employee', 'leaveType', 'approver'])
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->when(
                $request->filled('employee_id') && $request->string('employee_id') !== 'all',
                fn ($query) => $query->where('employee_id', (int) $request->integer('employee_id')),
            )
            ->latest()
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.hr.leaves.index', [
            'requests' => $requests,
            'leaveTypes' => LeaveType::query()->orderBy('name')->get(),
            'employees' => Employee::query()->orderBy('first_name')->orderBy('last_name')->get(),
            'statuses' => LeaveRequestStatus::cases(),
            'filters' => [
                'status' => (string) $request->string('status', 'all'),
                'employee_id' => (string) $request->string('employee_id', 'all'),
            ],
        ]);
    }

    public function storeType(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('hr.leaves.manage_types') ?? false, 403);

        $tenantId = $this->tenantContext->getTenantId();

        $data = $request->validate([
            'name' => [
                'required',
                'string',
                'max:120',
                Rule::unique('leave_types', 'name')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
            'annual_days' => ['required', 'integer', 'min:1', 'max:365'],
            'requires_approval' => ['sometimes', 'boolean'],
        ]);

        LeaveType::query()->create([
            'name' => $data['name'],
            'annual_days' => $data['annual_days'],
            'requires_approval' => $request->boolean('requires_approval', true),
        ]);

        flash()->success('تم إنشاء نوع الإجازة بنجاح.');

        return redirect()->route('hr.leaves.index');
    }

    public function storeRequest(Request $request): RedirectResponse
    {
        abort_unless($request->user()?->can('hr.leaves.create') ?? false, 403);

        $tenantId = $this->tenantContext->getTenantId();

        $data = $request->validate([
            'employee_id' => [
                'required',
                'integer',
                Rule::exists('employees', 'id')->where(
                    fn ($query) => $query->where('tenant_id', $tenantId)->whereNull('deleted_at')
                ),
            ],
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
            'requires_manager_escalation' => ['sometimes', 'boolean'],
            'approval_level' => ['nullable', 'integer', 'min:1', 'max:5'],
        ]);

        $leaveType = LeaveType::query()->findOrFail($data['leave_type_id']);
        $daysCount = LeaveRequest::calculateDaysCount($data['start_date'], $data['end_date']);

        if ($daysCount < 1) {
            flash()->error('المدة المحددة تقع بالكامل ضمن عطلات رسمية. اختر تواريخاً أخرى.');

            return redirect()->back()->withInput();
        }

        $remaining = $leaveType->remainingDaysFor((int) $data['employee_id']);

        if ($daysCount > $remaining) {
            flash()->error("رصيد الإجازة غير كافٍ. المتبقي: {$remaining} يوم.");

            return redirect()->back()->withInput();
        }

        $status = $leaveType->requires_approval
            ? LeaveRequestStatus::Pending
            : LeaveRequestStatus::Approved;

        $requiresEscalation = $request->boolean('requires_manager_escalation');
        $approvalLevel = max(1, (int) ($data['approval_level'] ?? ($requiresEscalation ? 2 : 1)));

        $leaveRequest = LeaveRequest::query()->create([
            'employee_id' => $data['employee_id'],
            'leave_type_id' => $data['leave_type_id'],
            'start_date' => $data['start_date'],
            'end_date' => $data['end_date'],
            'reason' => $data['reason'] ?? null,
            'days_count' => $daysCount,
            'status' => $status,
            'requires_manager_escalation' => $requiresEscalation,
            'approval_level' => $approvalLevel,
            'current_approval_level' => 0,
            'approved_by' => $status === LeaveRequestStatus::Approved ? $request->user()?->id : null,
        ]);

        if ($status === LeaveRequestStatus::Approved) {
            $this->applyApprovedLeaveEffects($leaveRequest);
        }

        $this->auditor->log('leave.created', 'hr', $leaveRequest, [
            'days_count' => $daysCount,
            'requires_manager_escalation' => $requiresEscalation,
            'approval_level' => $approvalLevel,
        ]);

        event(new LeaveRequestSubmitted(
            $leaveRequest,
            $request->user()?->id,
        ));

        flash()->success('تم تقديم طلب الإجازة بنجاح.');

        return redirect()->back();
    }

    public function approve(LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('hr.leaves.approve') ?? false, 403);
        $this->ensureTenantLeaveRequest($leaveRequest);

        if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
            flash()->error('لا يمكن اعتماد طلب غير معلّق.');

            return redirect()->back();
        }

        $leaveType = $leaveRequest->leaveType;
        abort_unless($leaveType !== null, 404);

        $remaining = $leaveType->remainingDaysFor($leaveRequest->employee_id);

        if ($leaveRequest->days_count > $remaining) {
            flash()->error("رصيد الإجازة غير كافٍ. المتبقي: {$remaining} يوم.");

            return redirect()->back();
        }

        DB::transaction(function () use ($leaveRequest): void {
            $nextLevel = $leaveRequest->current_approval_level + 1;
            $needsEscalation = $leaveRequest->requires_manager_escalation
                && $nextLevel < max(1, $leaveRequest->approval_level);

            if ($needsEscalation) {
                $leaveRequest->update([
                    'current_approval_level' => $nextLevel,
                    'approved_by' => auth()->id(),
                    'rejection_reason' => null,
                ]);

                $this->auditor->log('leave.escalated', 'hr', $leaveRequest, [
                    'level' => $nextLevel,
                    'required' => $leaveRequest->approval_level,
                ]);

                return;
            }

            $leaveRequest->update([
                'status' => LeaveRequestStatus::Approved,
                'current_approval_level' => $nextLevel,
                'approved_by' => auth()->id(),
                'rejection_reason' => null,
            ]);

            $this->applyApprovedLeaveEffects($leaveRequest->fresh(['employee', 'leaveType']));

            $this->auditor->log('leave.approved', 'hr', $leaveRequest, [
                'days_count' => $leaveRequest->days_count,
                'employee_id' => $leaveRequest->employee_id,
            ]);
        });

        $leaveRequest->refresh();

        if ($leaveRequest->status === LeaveRequestStatus::Pending) {
            // Escalated to the next approval level — not yet a decision, so the
            // requester is deliberately not notified.
            flash()->info('تم تمرير الاعتماد للمستوى التالي (تصعيد المدير).');
        } else {
            event(new LeaveRequestDecided($leaveRequest, 'approved', auth()->id()));

            flash()->success('تم اعتماد طلب الإجازة وخصم الأيام من الرصيد.');
        }

        return redirect()->back();
    }

    public function reject(Request $request, LeaveRequest $leaveRequest): RedirectResponse
    {
        abort_unless(auth()->user()?->can('hr.leaves.approve') ?? false, 403);
        $this->ensureTenantLeaveRequest($leaveRequest);

        if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
            flash()->error('لا يمكن رفض طلب غير معلّق.');

            return redirect()->back();
        }

        $data = $request->validate([
            'rejection_reason' => ['nullable', 'string', 'max:2000'],
        ]);

        $leaveRequest->update([
            'status' => LeaveRequestStatus::Rejected,
            'approved_by' => auth()->id(),
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        $this->auditor->log('leave.rejected', 'hr', $leaveRequest, [
            'rejection_reason' => $data['rejection_reason'] ?? null,
        ]);

        event(new LeaveRequestDecided($leaveRequest->fresh(), 'rejected', auth()->id()));

        flash()->info('تم رفض طلب الإجازة.');

        return redirect()->back();
    }

    private function applyApprovedLeaveEffects(LeaveRequest $leaveRequest): void
    {
        if (! $leaveRequest->coversDate(now())) {
            return;
        }

        $employee = $leaveRequest->employee;
        if ($employee === null) {
            return;
        }

        if ($employee->status === EmployeeStatus::Active) {
            $employee->update(['status' => EmployeeStatus::OnLeave]);
        }
    }

    private function ensureTenantLeaveRequest(LeaveRequest $leaveRequest): void
    {
        abort_unless(
            (int) $leaveRequest->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
