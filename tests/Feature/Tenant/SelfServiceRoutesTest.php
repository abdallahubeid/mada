<?php

use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

afterEach(function () {
    Carbon::setTestNow();
});

test('the retired my-space routes no longer exist', function () {
    foreach ([
        'hr.my-space',
        'hr.my-space.check-in',
        'hr.my-space.check-out',
        'hr.my-space.leaves.store',
    ] as $name) {
        expect(Route::has($name))->toBeFalse("route {$name} should have been removed");
    }

    // The old URL is gone too, not merely unnamed.
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);
    $this->get('/app/my-space')->assertNotFound();
});

test('hr.my_space.view is no longer part of the permission catalog', function () {
    expect(TenantPermissionCatalog::all())->not->toContain('hr.my_space.view')
        ->and(TenantPermissionCatalog::selfServicePermissions())->not->toContain('hr.my_space.view');

    // The self-service bucket still grants the four standalone features.
    expect(TenantPermissionCatalog::selfServicePermissions())
        ->toContain('hr.attendance.check_in_out')
        ->toContain('hr.my_leaves.view')
        ->toContain('hr.my_evaluations.view')
        ->toContain('hr.my_tasks.view');
});

test('an employee can reach all four standalone self-service pages', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
        'first_name' => 'Lina',
        'last_name' => 'Faris',
    ]);

    $this->get(route('tenant.hr.my-attendance'))->assertOk()->assertSee('تسجيل الحضور والانصراف', false);
    $this->get(route('tenant.hr.my-leaves'))->assertOk()->assertSee('طلبات الإجازة', false);
    $this->get(route('tenant.hr.my-evaluations'))->assertOk()->assertSee('تقييماتي', false);
    $this->get(route('tenant.hr.my-tasks'))->assertOk()->assertSee('مهامي', false);
});

test('the sidebar lists the four self-service items and no my space link', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
    ]);

    $html = $this->get(route('tenant.hr.my-attendance'))->assertOk()->getContent();

    expect($html)->toContain('🕒 تسجيل الحضور والانصراف')
        ->toContain('📝 طلبات الإجازة')
        ->toContain('⭐ تقييماتي')
        ->toContain('🗂️ مهامي')
        ->not->toContain('مساحتي الخاصة');

    // Each item points at its own route, not a ?tab= query on a shared hub.
    expect($html)->toContain(route('tenant.hr.my-attendance'))
        ->toContain(route('tenant.hr.my-leaves'))
        ->toContain(route('tenant.hr.my-evaluations'))
        ->toContain(route('tenant.hr.my-tasks'));
});

test('each self-service page highlights only its own sidebar item', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
    ]);

    // Under the old hub every self-service link shared the `hr.my-space*`
    // pattern, so all of them lit up at once. Each route now owns its pattern.
    $activeLinkFor = function (string $route) {
        $html = $this->get(route($route))->assertOk()->getContent();

        preg_match_all(
            '/<a[^>]*href="([^"]*)"[^>]*class="[^"]*border-emerald-400 bg-emerald-400\/10[^"]*"/',
            $html,
            $matches
        );

        return $matches[1] ?? [];
    };

    expect($activeLinkFor('tenant.hr.my-attendance'))->toBe([route('tenant.hr.my-attendance')]);
    expect($activeLinkFor('tenant.hr.my-leaves'))->toBe([route('tenant.hr.my-leaves')]);
    expect($activeLinkFor('tenant.hr.my-tasks'))->toBe([route('tenant.hr.my-tasks')]);
});

test('employee can check in and out from the standalone attendance page', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-03 08:40:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
    ]);

    $this->post(route('tenant.hr.my-attendance.check-in'))
        ->assertRedirect(route('tenant.hr.my-attendance'));

    $attendance = Attendance::query()
        ->where('employee_id', $employee->id)
        ->whereDate('date', '2026-08-03')
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->check_in)->not->toBeNull();

    Carbon::setTestNow(Carbon::parse('2026-08-03 17:15:00'));

    $this->post(route('tenant.hr.my-attendance.check-out'))
        ->assertRedirect(route('tenant.hr.my-attendance'));

    expect($attendance->fresh()->check_out)->not->toBeNull();
});

test('employee can submit a leave request from the standalone leaves page', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'user_id' => $user->id,
    ]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'سنوية',
        'annual_days' => 14,
        'requires_approval' => true,
    ]);

    $this->post(route('tenant.hr.my-leaves.store'), [
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(5)->toDateString(),
        'end_date' => now()->addDays(7)->toDateString(),
        'reason' => 'Personal travel',
    ])->assertRedirect(route('tenant.hr.my-leaves'));

    $leaveRequest = LeaveRequest::query()->where('employee_id', $employee->id)->first();

    expect($leaveRequest)->not->toBeNull()
        ->and($leaveRequest->status)->toBe(LeaveRequestStatus::Pending)
        ->and($leaveRequest->days_count)->toBe(3)
        ->and($leaveRequest->reason)->toBe('Personal travel');
});

test('self-service pages show a graceful notice when no employee profile is linked', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('tenant.hr.my-attendance'))
        ->assertOk()
        ->assertSee('حسابك غير مرتبط بملف موظف', false);

    $this->get(route('tenant.hr.my-leaves'))
        ->assertOk()
        ->assertSee('حسابك غير مرتبط بملف موظف', false);
});

test('self-service data never leaks between employees', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $meUser = User::factory()->create(['tenant_id' => $owner->tenant_id, 'is_active' => true]);
    $meUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $me = Employee::factory()->create(['tenant_id' => $owner->tenant_id, 'user_id' => $meUser->id]);

    $colleague = Employee::factory()->create(['tenant_id' => $owner->tenant_id]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'name' => 'سنوية',
        'annual_days' => 14,
    ]);

    LeaveRequest::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'employee_id' => $colleague->id,
        'leave_type_id' => $leaveType->id,
        'status' => LeaveRequestStatus::Approved,
        'reason' => 'إجازة زميلي السرية',
    ]);

    Attendance::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'employee_id' => $colleague->id,
        'date' => now()->toDateString(),
    ]);

    $this->actingAs($meUser)
        ->get(route('tenant.hr.my-leaves'))
        ->assertOk()
        ->assertDontSee('إجازة زميلي السرية', false);

    expect(Attendance::query()->where('employee_id', $me->id)->count())->toBe(0);
});

test('a user without the self-service permissions is forbidden', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);
    $user->syncRoles([]);

    $this->get(route('tenant.hr.my-attendance'))->assertForbidden();
    $this->get(route('tenant.hr.my-leaves'))->assertForbidden();
    $this->post(route('tenant.hr.my-attendance.check-in'))->assertForbidden();
    $this->post(route('tenant.hr.my-leaves.store'), [])->assertForbidden();
});
