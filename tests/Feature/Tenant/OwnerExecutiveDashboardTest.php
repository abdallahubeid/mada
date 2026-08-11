<?php

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\AuditLog;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Services\Tenancy\ExecutiveDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('dashboard analytics compute kpis and series accurately for the owner', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-15 10:00:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'name' => 'Acme Robotics',
    ], ['name' => 'Jane Owner']);

    $engineering = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Engineering',
    ]);

    $ops = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Operations',
    ]);

    $employeeA = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'department_id' => $engineering->id,
        'status' => EmployeeStatus::Active,
        'first_name' => 'Sara',
        'last_name' => 'Ali',
    ]);

    $employeeB = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'department_id' => $engineering->id,
        'status' => EmployeeStatus::Active,
    ]);

    Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'department_id' => $ops->id,
        'status' => EmployeeStatus::Resigned,
        'updated_at' => now()->subDays(10),
    ]);

    EmployeeContract::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeA->id,
        'status' => ContractStatus::Active,
        'end_date' => now()->addDays(10)->toDateString(),
    ]);

    EmployeeContract::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeB->id,
        'status' => ContractStatus::Terminated,
    ]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $user->tenant_id,
        'annual_days' => 20,
        'requires_approval' => true,
    ]);

    LeaveRequest::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeA->id,
        'leave_type_id' => $leaveType->id,
        'status' => LeaveRequestStatus::Pending,
        'requires_manager_escalation' => true,
        'approval_level' => 2,
        'current_approval_level' => 0,
    ]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeA->id,
        'date' => '2026-08-01',
        'status' => AttendanceStatus::Present,
    ]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeB->id,
        'date' => '2026-08-02',
        'status' => AttendanceStatus::Absent,
    ]);

    $payload = app(ExecutiveDashboard::class)->build();

    expect($payload['kpis']['total_employees'])->toBe(3)
        ->and($payload['kpis']['active_contracts'])->toBe(1)
        ->and($payload['kpis']['pending_leaves'])->toBe(1)
        ->and($payload['kpis']['attendance_rate'])->toBe(50.0)
        ->and($payload['pipeline']['resigned_90d'])->toBe(1)
        ->and($payload['pendingLeaves'])->toHaveCount(1)
        ->and($payload['expiringContracts'])->toHaveCount(1)
        ->and($payload['departmentChart']['labels'])->toContain('Engineering')
        ->and($payload['attendanceChart']['labels'])->toHaveCount(6);

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('Jane Owner')
        ->assertSee('Acme Robotics')
        ->assertSee('إجمالي الموظفين')
        ->assertSee('data-testid="kpi-total-employees"', false)
        ->assertSee('>3</p>', false)
        ->assertSee('إجراءات سريعة للاعتماد')
        ->assertSee('Sara Ali');

    Carbon::setTestNow();
});

test('audit logs record critical actions when models change', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'status' => EmployeeStatus::Active,
        'first_name' => 'Omar',
        'last_name' => 'Hassan',
        'department_id' => null,
        'manager_id' => null,
    ]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'سنوية',
        'annual_days' => 14,
        'requires_approval' => true,
    ]);

    $this->post(route('hr.leaves.requests.store'), [
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(6)->toDateString(),
        'reason' => 'Travel',
        'requires_manager_escalation' => '1',
        'approval_level' => 2,
    ])->assertRedirect();

    $leaveRequest = LeaveRequest::query()->where('employee_id', $employee->id)->first();

    expect($leaveRequest)->not->toBeNull()
        ->and($leaveRequest->requires_manager_escalation)->toBeTrue()
        ->and($leaveRequest->approval_level)->toBe(2);

    expect(AuditLog::query()->where('action', 'leave.created')->exists())->toBeTrue();

    $this->post(route('hr.leaves.approve', $leaveRequest))->assertRedirect();

    $leaveRequest->refresh();

    expect($leaveRequest->status)->toBe(LeaveRequestStatus::Pending)
        ->and($leaveRequest->current_approval_level)->toBe(1)
        ->and(AuditLog::query()->where('action', 'leave.escalated')->exists())->toBeTrue();

    $this->post(route('hr.leaves.approve', $leaveRequest))->assertRedirect();

    $leaveRequest->refresh();

    expect($leaveRequest->status)->toBe(LeaveRequestStatus::Approved)
        ->and(AuditLog::query()->where('action', 'leave.approved')->exists())->toBeTrue();

    $this->put(route('settings.company.update'), [
        'currency' => 'SAR',
        'timezone' => 'Asia/Riyadh',
        'evaluation_periodicity' => 'quarterly',
        'working_days' => [0, 1, 2, 3, 4],
        'holidays' => [],
    ])->assertRedirect(route('settings.company'));

    expect(AuditLog::query()->where('action', 'settings.updated')->exists())->toBeTrue();
});

test('audit logs view is accessible only by owner and renders human-readable details without raw json', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    AuditLog::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'user_id' => $owner->id,
        'action' => 'employee.created',
        'module' => 'hr',
        'changes' => ['full_name' => 'Demo'],
    ]);

    AuditLog::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'user_id' => $owner->id,
        'action' => 'role.updated',
        'module' => 'rbac',
        'changes' => [
            'role' => 'Owner',
            'permissions' => ['tenant.dashboard.view', 'hr.employees.view_any'],
        ],
    ]);

    $this->get(route('tenant.audit-logs.index'))
        ->assertOk()
        ->assertSee('سجل النشاط')
        ->assertSee('إضافة موظف جديد: Demo')
        ->assertSee('تعديل أذونات دور: المالك (Owner)')
        ->assertSee('الموارد البشرية')
        ->assertSee('الأدوار والصلاحيات')
        ->assertSee('الاسم الكامل: Demo')
        ->assertSee('معاينة التفاصيل')
        ->assertSee('القيم السابقة ↔ القيم الجديدة')
        ->assertDontSee('employee.created')
        ->assertDontSee('role.updated')
        ->assertDontSee('JSON كامل')
        ->assertDontSee('"full_name"')
        ->assertDontSee('"permissions"');

    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $this->get(route('tenant.audit-logs.index'))->assertForbidden();

    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.audit-logs.index'))->assertForbidden();
});

test('owner and hr manager can export reports and owner can export audit logs', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Lina',
        'last_name' => 'Saleh',
    ]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'date' => now()->toDateString(),
        'status' => AttendanceStatus::Present,
    ]);

    $auditLog = AuditLog::query()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'action' => 'settings.updated',
        'module' => 'settings',
        'changes' => ['currency' => 'SAR', 'timezone' => 'Asia/Riyadh'],
        'ip_address' => '127.0.0.1',
    ]);

    $auditLog = $auditLog->fresh();

    expect($auditLog)->not->toBeNull()
        ->and($auditLog->created_at)->not->toBeNull()
        ->and(AuditLog::query()->count())->toBe(1);

    $this->get(route('tenant.reports.index'))
        ->assertOk()
        ->assertSee('التقارير والتصدير')
        ->assertSee('سجل النشاط والأمان');

    $this->get(route('tenant.reports.attendance', ['format' => 'csv']))
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    $this->get(route('tenant.reports.employees', ['format' => 'pdf']))
        ->assertOk()
        ->assertSee('كشف الموظفين')
        ->assertSee('Lina Saleh');

    $from = $auditLog->created_at->copy()->subDay()->toDateString();
    $to = $auditLog->created_at->copy()->addDay()->toDateString();

    $this->get(route('tenant.reports.audit-logs', [
        'format' => 'pdf',
        'from' => $from,
        'to' => $to,
        'module' => 'all',
    ]))
        ->assertOk()
        ->assertSee('سجل النشاط والأمان')
        ->assertSee('تحديث إعدادات المؤسسة')
        ->assertSee('الإعدادات')
        ->assertDontSee('"currency"')
        ->assertDontSee('JSON');

    $auditCsv = $this->get(route('tenant.reports.audit-logs', [
        'format' => 'csv',
        'from' => $from,
        'to' => $to,
        'module' => 'settings',
    ]));

    $auditCsv
        ->assertOk()
        ->assertHeader('content-type', 'text/csv; charset=UTF-8');

    expect($auditCsv->streamedContent())
        ->toContain('تحديث إعدادات المؤسسة')
        ->toContain('الإعدادات')
        ->not->toContain('"currency"');

    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $this->get(route('tenant.reports.index'))
        ->assertOk()
        ->assertDontSee('سجل النشاط والأمان');

    $this->get(route('tenant.reports.audit-logs', ['format' => 'csv']))->assertForbidden();

    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.reports.index'))->assertForbidden();
});
