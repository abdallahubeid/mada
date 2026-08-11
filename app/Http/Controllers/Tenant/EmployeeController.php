<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\AssetAssignment;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Events\Tenancy\EmployeeCreated;
use App\Events\Tenancy\EmployeeStatusChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmployeeRequest;
use App\Http\Requests\Tenant\UpdateEmployeeRequest;
use App\Mail\Tenancy\EmployeeWelcomeMail;
use App\Models\User;
use App\Services\Tenancy\PlanLimitGuard;
use App\Services\Tenancy\TenantAuditor;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class EmployeeController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditor $auditor,
        private readonly PlanLimitGuard $planLimitGuard,
        private readonly TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $employees = Employee::query()
            ->with(['department'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $q = '%'.mb_strtolower((string) $request->string('q')).'%';
                $query->where(function ($inner) use ($q): void {
                    $inner->whereRaw('LOWER(first_name) like ?', [$q])
                        ->orWhereRaw('LOWER(last_name) like ?', [$q])
                        ->orWhereRaw('LOWER(job_title) like ?', [$q]);
                });
            })
            ->when(
                $request->filled('department_id') && $request->string('department_id') !== 'all',
                fn ($query) => $query->where('department_id', (int) $request->integer('department_id')),
            )
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.hr.employees.index', [
            'employees' => $employees,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'statuses' => EmployeeStatus::cases(),
            'filters' => [
                'q' => (string) $request->string('q'),
                'department_id' => (string) $request->string('department_id', 'all'),
                'status' => (string) $request->string('status', 'all'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('tenant.hr.employees.create', [
            'employee' => new Employee(['status' => EmployeeStatus::Active, 'joining_date' => now()->toDateString()]),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreEmployeeRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        abort_unless($tenant !== null, 403);

        $limit = $this->planLimitGuard->assertCanCreate($tenant, 'employees');
        if (! $limit['allowed']) {
            flash()->error("وصلت إلى الحد الأقصى للموظفين في خطتك ({$limit['used']}/{$limit['limit']}).");

            return redirect()->back()->withInput();
        }

        $data = $request->validated();

        $employee = DB::transaction(function () use ($data, $request, $tenant): Employee {
            $payload = $this->employeePayload($data);

            if ($request->hasFile('avatar')) {
                $payload['avatar_path'] = $this->storeUpload($request->file('avatar'), 'avatars');
            }

            if ($request->hasFile('cv')) {
                $payload['cv_path'] = $this->storeUpload($request->file('cv'), 'cvs');
            }

            $employee = Employee::query()->create($payload);

            if ($data['create_user_account']) {
                $this->attachUserAccount($employee, $data, $tenant->id);
            }

            return $employee;
        });

        $this->auditor->log('employee.created', 'hr', $employee, [
            'full_name' => $employee->full_name,
            'job_title' => $employee->job_title,
        ]);

        event(new EmployeeCreated($employee));
        $this->planLimitGuard->evaluateAfterCreate($tenant, 'employees');

        flash()->success('تم إنشاء ملف الموظف بنجاح.');

        return redirect()->route('hr.employees.show', $employee);
    }

    public function show(Employee $employee): View
    {
        $this->ensureTenantEmployee($employee);

        $employee->load(['department', 'manager', 'user', 'subordinates']);

        $activeContract = EmployeeContract::query()
            ->where('employee_id', $employee->id)
            ->where('status', ContractStatus::Active)
            ->orderByDesc('start_date')
            ->first();

        $attendances = Attendance::query()
            ->where('employee_id', $employee->id)
            ->orderByDesc('date')
            ->paginate(config('app.paginate_page'), ['*'], 'attendance_page');

        $todayAttendance = Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first();

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

        $leaveRequests = LeaveRequest::query()
            ->with('leaveType')
            ->where('employee_id', $employee->id)
            ->latest()
            ->paginate(config('app.paginate_page'), ['*'], 'leave_page');

        $evaluations = EmployeeEvaluation::query()
            ->with('evaluator')
            ->where('employee_id', $employee->id)
            ->latest()
            ->paginate(config('app.paginate_page'), ['*'], 'evaluation_page');

        $activeAssetAssignments = AssetAssignment::query()
            ->with('asset')
            ->where('employee_id', $employee->id)
            ->whereNull('returned_at')
            ->latest('assigned_at')
            ->get();

        $assetAssignmentHistory = AssetAssignment::query()
            ->with('asset')
            ->where('employee_id', $employee->id)
            ->latest('assigned_at')
            ->paginate(config('app.paginate_page'), ['*'], 'asset_page');

        return view('tenant.hr.employees.show', [
            'employee' => $employee,
            'activeContract' => $activeContract,
            'attendances' => $attendances,
            'todayAttendance' => $todayAttendance,
            'leaveBalances' => $leaveBalances,
            'leaveTypes' => $leaveTypes,
            'leaveRequests' => $leaveRequests,
            'evaluations' => $evaluations,
            'activeAssetAssignments' => $activeAssetAssignments,
            'assetAssignmentHistory' => $assetAssignmentHistory,
        ]);
    }

    public function edit(Employee $employee): View
    {
        $this->ensureTenantEmployee($employee);

        return view('tenant.hr.employees.edit', [
            'employee' => $employee->load('user'),
            ...$this->formOptions($employee->id),
        ]);
    }

    public function update(UpdateEmployeeRequest $request, Employee $employee): RedirectResponse
    {
        $this->ensureTenantEmployee($employee);
        $tenant = $this->tenantContext->getTenant();
        abort_unless($tenant !== null, 403);

        $data = $request->validated();
        $previousStatus = $employee->status;

        DB::transaction(function () use ($employee, $data, $request, $tenant): void {
            $payload = $this->employeePayload($data);

            if ($data['remove_avatar'] && $employee->avatar_path) {
                Storage::disk('custom')->delete($employee->avatar_path);
                $payload['avatar_path'] = null;
            }

            if ($data['remove_cv'] && $employee->cv_path) {
                Storage::disk('custom')->delete($employee->cv_path);
                $payload['cv_path'] = null;
            }

            if ($request->hasFile('avatar')) {
                if ($employee->avatar_path) {
                    Storage::disk('custom')->delete($employee->avatar_path);
                }
                $payload['avatar_path'] = $this->storeUpload($request->file('avatar'), 'avatars');
            }

            if ($request->hasFile('cv')) {
                if ($employee->cv_path) {
                    Storage::disk('custom')->delete($employee->cv_path);
                }
                $payload['cv_path'] = $this->storeUpload($request->file('cv'), 'cvs');
            }

            $employee->update($payload);

            if ($data['create_user_account'] && $employee->user_id === null) {
                $this->attachUserAccount($employee, $data, $tenant->id);
            }
        });

        $employee->refresh();

        if ($previousStatus !== $employee->status) {
            event(new EmployeeStatusChanged(
                $employee,
                $previousStatus,
                $employee->status,
                false,
                $request->user()?->id,
            ));
        }

        flash()->info('تم تحديث ملف الموظف بنجاح.');

        return redirect()->route('hr.employees.show', $employee);
    }

    public function destroy(Employee $employee): RedirectResponse
    {
        $this->ensureTenantEmployee($employee);

        if ($employee->subordinates()->exists()) {
            flash()->error('لا يمكن حذف موظف لديه مرؤوسون. أعد تعيين المدير المباشر أولاً.');

            return redirect()->route('hr.employees.index');
        }

        if (Department::query()->where('department_head_id', $employee->id)->exists()) {
            flash()->error('لا يمكن حذف موظف معيّن كرئيس قسم. أزل التعيين أولاً.');

            return redirect()->route('hr.employees.index');
        }

        if ($employee->avatar_path) {
            Storage::disk('custom')->delete($employee->avatar_path);
        }

        if ($employee->cv_path) {
            Storage::disk('custom')->delete($employee->cv_path);
        }

        $this->auditor->log('employee.deleted', 'hr', $employee, [
            'full_name' => $employee->full_name,
            'job_title' => $employee->job_title,
        ]);

        $previousStatus = $employee->status;

        $employee->delete();

        event(new EmployeeStatusChanged(
            $employee,
            $previousStatus,
            null,
            true,
            request()->user()?->id,
        ));

        $this->trash->flashSoftDeleted('تم حذف ملف الموظف بنجاح.', 'employees', $employee);

        return redirect()->route('hr.employees.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(?int $excludeEmployeeId = null): array
    {
        return [
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'managers' => Employee::query()
                ->when($excludeEmployeeId !== null, fn ($query) => $query->whereKeyNot($excludeEmployeeId))
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->mapWithKeys(fn (Employee $employee): array => [
                    $employee->id => $employee->full_name,
                ]),
            'statuses' => EmployeeStatus::cases(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function employeePayload(array $data): array
    {
        return [
            'first_name' => $data['first_name'],
            'last_name' => $data['last_name'],
            'national_id' => $data['national_id'] ?? null,
            'phone' => $data['phone'] ?? null,
            'address' => $data['address'] ?? null,
            'job_title' => $data['job_title'],
            'joining_date' => $data['joining_date'],
            'status' => $data['status'],
            'department_id' => $data['department_id'] ?? null,
            'manager_id' => $data['manager_id'] ?? null,
        ];
    }

    private function storeUpload(UploadedFile $file, string $collection): string
    {
        $tenantId = $this->tenantContext->getTenantId();

        return $file->store("tenant/{$tenantId}/employees/{$collection}", 'custom');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function attachUserAccount(Employee $employee, array $data, int $tenantId): void
    {
        $plainPassword = ! empty($data['auto_generate_password'])
            ? Str::password(12)
            : (string) ($data['password'] ?? '');

        $user = User::query()->create([
            'tenant_id' => $tenantId,
            'department_id' => $employee->department_id,
            'name' => $employee->full_name,
            'email' => strtolower((string) $data['email']),
            'password' => $plainPassword,
            'phone' => $employee->phone,
            'job_title' => $employee->job_title,
            'is_active' => $employee->status === EmployeeStatus::Active,
        ]);

        $user->syncRoles([TenantPermissionCatalog::ROLE_EMPLOYEE]);

        $employee->forceFill(['user_id' => $user->id])->save();

        $tenant = $this->tenantContext->getTenant();
        if ($tenant !== null) {
            Mail::to($user->email)->send(new EmployeeWelcomeMail(
                $user,
                $tenant,
                $plainPassword,
                TenantPermissionCatalog::roleLabels()[TenantPermissionCatalog::ROLE_EMPLOYEE],
            ));
        }
    }

    private function ensureTenantEmployee(Employee $employee): void
    {
        abort_unless(
            (int) $employee->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
