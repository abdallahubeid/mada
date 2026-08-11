<?php

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
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\Task;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Services\Tenancy\EmployeeDashboard;
use App\Services\Tenancy\HrDashboard;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('the dashboard dispatcher routes each role to its own dashboard', function () {
    // Owner keeps the executive dashboard rendered in place.
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $this->get(route('dashboard'))->assertOk()->assertSee('إجمالي الموظفين', false);

    // HR Manager is redirected to the HR dashboard.
    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);
    $this->get(route('dashboard'))->assertRedirect(route('tenant.hr.dashboard'));

    // Employee is redirected to the personal dashboard.
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);
    $this->get(route('dashboard'))->assertRedirect(route('tenant.hr.employee.dashboard'));
});

test('an employee cannot reach the hr dashboard', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.hr.dashboard'))->assertForbidden();
});

test('hr dashboard counts employees with no attendance row as absent', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    // 4 active employees; only 2 have an attendance row today.
    $employees = Employee::factory()->count(4)->create([
        'tenant_id' => $user->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employees[0]->id,
        'date' => now()->toDateString(),
        'status' => AttendanceStatus::Present,
    ]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employees[1]->id,
        'date' => now()->toDateString(),
        'status' => AttendanceStatus::Late,
    ]);

    $data = app(HrDashboard::class)->build();

    expect($data['attendanceToday']['headcount'])->toBe(4)
        ->and($data['attendanceToday']['present'])->toBe(1)
        ->and($data['attendanceToday']['late'])->toBe(1)
        ->and($data['attendanceToday']['absent'])->toBe(0)
        // The two employees with no row at all must not vanish from the numbers.
        ->and($data['attendanceToday']['no_record'])->toBe(2)
        ->and($data['attendanceToday']['absence_rate'])->toBe(50.0);
});

test('hr dashboard rolls up tasks org-wide and flags overdue', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $manager = Employee::factory()->create(['tenant_id' => $user->tenant_id]);
    $worker = Employee::factory()->create(['tenant_id' => $user->tenant_id, 'manager_id' => $manager->id]);

    $base = ['tenant_id' => $user->tenant_id, 'manager_id' => $manager->id, 'employee_id' => $worker->id];

    Task::factory()->create([...$base, 'status' => TaskStatus::Todo, 'due_date' => now()->subDay()]);
    Task::factory()->create([...$base, 'status' => TaskStatus::InProgress, 'due_date' => now()->addDay()]);
    Task::factory()->create([...$base, 'status' => TaskStatus::Review, 'due_date' => now()->subDays(3)]);
    // Completed-but-past-due must NOT count as overdue.
    Task::factory()->create([...$base, 'status' => TaskStatus::Completed, 'due_date' => now()->subDays(5)]);

    $tasks = app(HrDashboard::class)->build()['tasks'];

    expect($tasks['total'])->toBe(4)
        ->and($tasks['by_status'][TaskStatus::Todo->value])->toBe(1)
        ->and($tasks['by_status'][TaskStatus::Completed->value])->toBe(1)
        ->and($tasks['overdue'])->toBe(2);
});

test('hr dashboard reports evaluation completion for the current period', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $employees = Employee::factory()->count(4)->create([
        'tenant_id' => $user->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);

    $periodKey = '2026-Q3';

    EmployeeEvaluation::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employees[0]->id,
        'period_type' => EvaluationPeriodType::Quarterly,
        'period_key' => $periodKey,
        'status' => EvaluationStatus::Approved,
    ]);

    EmployeeEvaluation::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employees[1]->id,
        'period_type' => EvaluationPeriodType::Quarterly,
        'period_key' => $periodKey,
        'status' => EvaluationStatus::Submitted,
    ]);

    EmployeeEvaluation::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employees[2]->id,
        'period_type' => EvaluationPeriodType::Quarterly,
        'period_key' => $periodKey,
        'status' => EvaluationStatus::Draft,
    ]);

    $evaluations = app(HrDashboard::class)->build()['evaluations'];

    expect($evaluations['period_key'])->toBe($periodKey)
        ->and($evaluations['headcount'])->toBe(4)
        ->and($evaluations['approved'])->toBe(1)
        ->and($evaluations['submitted'])->toBe(1)
        ->and($evaluations['draft'])->toBe(1)
        ->and($evaluations['not_started'])->toBe(1)
        // Draft is not "done" — only approved + submitted count toward completion.
        ->and($evaluations['completion_rate'])->toBe(50.0);
});

test('hr dashboard surfaces work anniversaries but not first-year joiners', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    // Joined 3 years ago, anniversary in 10 days → shown as "3 years".
    Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'status' => EmployeeStatus::Active,
        'first_name' => 'ثلاث',
        'last_name' => 'سنوات',
        'joining_date' => '2023-08-15',
    ]);

    // Joined 20 days ago — the upcoming date is 0 years away, not an anniversary.
    Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'status' => EmployeeStatus::Active,
        'first_name' => 'موظف',
        'last_name' => 'جديد',
        'joining_date' => '2026-08-20',
    ]);

    // Anniversary is 6 months out — outside the 30-day window.
    Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'status' => EmployeeStatus::Active,
        'first_name' => 'بعيد',
        'last_name' => 'الموعد',
        'joining_date' => '2022-02-01',
    ]);

    $anniversaries = app(HrDashboard::class)->build()['anniversaries'];

    expect($anniversaries)->toHaveCount(1)
        ->and($anniversaries[0]['employee']->full_name)->toBe('ثلاث سنوات')
        ->and($anniversaries[0]['years'])->toBe(3);
});

test('employee dashboard shows only the acting employee own records', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $meUser = User::factory()->create(['tenant_id' => $owner->tenant_id, 'is_active' => true]);
    $meUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $me = Employee::factory()->create(['tenant_id' => $owner->tenant_id, 'user_id' => $meUser->id]);

    $colleague = Employee::factory()->create(['tenant_id' => $owner->tenant_id]);

    $manager = Employee::factory()->create(['tenant_id' => $owner->tenant_id]);

    Task::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'manager_id' => $manager->id,
        'employee_id' => $me->id,
        'status' => TaskStatus::InProgress,
        'title' => 'مهمتي أنا',
        'due_date' => now()->subDay(),
    ]);

    Task::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'manager_id' => $manager->id,
        'employee_id' => $colleague->id,
        'status' => TaskStatus::Todo,
        'title' => 'مهمة زميلي',
    ]);

    $data = app(EmployeeDashboard::class)->build($me);

    expect($data['tasks']['total'])->toBe(1)
        ->and($data['tasks']['open'])->toBe(1)
        ->and($data['tasks']['overdue'])->toBe(1)
        ->and($data['tasks']['by_status'][TaskStatus::InProgress->value])->toBe(1)
        ->and($data['tasks']['by_status'][TaskStatus::Todo->value])->toBe(0);

    $this->actingAs($meUser)
        ->get(route('tenant.hr.employee.dashboard'))
        ->assertOk()
        ->assertSee('مهمتي أنا', false)
        ->assertDontSee('مهمة زميلي', false);
});

test('employee dashboard hides draft evaluations and reports leave balance', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $meUser = User::factory()->create(['tenant_id' => $owner->tenant_id, 'is_active' => true]);
    $meUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $me = Employee::factory()->create(['tenant_id' => $owner->tenant_id, 'user_id' => $meUser->id]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'annual_days' => 20,
        'requires_approval' => true,
    ]);

    LeaveRequest::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'employee_id' => $me->id,
        'leave_type_id' => $leaveType->id,
        'status' => LeaveRequestStatus::Approved,
        'start_date' => '2026-03-01',
        'end_date' => '2026-03-05',
        'days_count' => 5,
    ]);

    // Draft must stay invisible — it is the evaluator's private working copy.
    EmployeeEvaluation::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'employee_id' => $me->id,
        'period_type' => EvaluationPeriodType::Quarterly,
        'period_key' => '2026-Q3',
        'status' => EvaluationStatus::Draft,
        'rating' => 4.5,
    ]);

    $data = app(EmployeeDashboard::class)->build($me);

    expect($data['remainingLeaveDays'])->toBe(15)
        ->and($data['latestEvaluation'])->toBeNull();

    EmployeeEvaluation::query()->where('employee_id', $me->id)
        ->update(['status' => EvaluationStatus::Submitted]);

    $refreshed = app(EmployeeDashboard::class)->build($me->fresh());

    expect($refreshed['latestEvaluation'])->not->toBeNull()
        ->and((float) $refreshed['latestEvaluation']['evaluation']->rating)->toBe(4.5);
});

test('employee dashboard renders an empty state when no employee profile is linked', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.hr.employee.dashboard'))
        ->assertOk()
        ->assertSee('حسابك غير مرتبط بملف موظف', false);
});

test('hr dashboard metrics never leak across tenants', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-05 10:00:00'));

    // Tenant A: 3 employees, 1 pending leave.
    $ownerA = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employeesA = Employee::factory()->count(3)->create([
        'tenant_id' => $ownerA->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);
    $typeA = LeaveType::factory()->create(['tenant_id' => $ownerA->tenant_id]);
    LeaveRequest::factory()->create([
        'tenant_id' => $ownerA->tenant_id,
        'employee_id' => $employeesA[0]->id,
        'leave_type_id' => $typeA->id,
        'status' => LeaveRequestStatus::Pending,
    ]);
    EmployeeContract::factory()->create([
        'tenant_id' => $ownerA->tenant_id,
        'employee_id' => $employeesA[0]->id,
        'status' => ContractStatus::Active,
        'end_date' => now()->addDays(10),
    ]);

    $dataA = app(HrDashboard::class)->build();

    expect($dataA['kpis']['headcount'])->toBe(3)
        ->and($dataA['kpis']['pending_leaves'])->toBe(1)
        ->and($dataA['expiringContracts'])->toHaveCount(1);

    // Tenant B sees none of tenant A's data.
    $hrB = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);
    Employee::factory()->count(2)->create([
        'tenant_id' => $hrB->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);

    $dataB = app(HrDashboard::class)->build();

    expect($dataB['kpis']['headcount'])->toBe(2)
        ->and($dataB['kpis']['pending_leaves'])->toBe(0)
        ->and($dataB['expiringContracts'])->toHaveCount(0);
});
