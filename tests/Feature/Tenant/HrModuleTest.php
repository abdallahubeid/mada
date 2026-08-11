<?php

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('owner can complete a full departments crud cycle under hr routes', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('hr.departments.index'))
        ->assertOk()
        ->assertSee('الأقسام');

    $this->get(route('hr.departments.create'))->assertOk();

    $this->post(route('hr.departments.store'), [
        'name' => 'Human Resources',
        'code' => 'HR',
        'description' => 'People ops',
        'parent_id' => null,
        'department_head_id' => null,
    ])
        ->assertRedirect(route('hr.departments.index'))
        ->assertSessionHas('flasher');

    $department = Department::query()->where('code', 'HR')->first();

    expect($department)->not->toBeNull()
        ->and($department->tenant_id)->toBe($user->tenant_id)
        ->and($department->name)->toBe('Human Resources');

    $this->get(route('hr.departments.index'))
        ->assertOk()
        ->assertSee('0');

    $this->get(route('hr.departments.edit', $department))->assertOk();

    $this->put(route('hr.departments.update', $department), [
        'name' => 'People',
        'code' => 'PEO',
        'description' => 'Updated',
        'parent_id' => null,
        'department_head_id' => null,
    ])->assertRedirect(route('hr.departments.index'));

    expect($department->fresh()->name)->toBe('People');

    $this->delete(route('hr.departments.destroy', $department))
        ->assertRedirect(route('hr.departments.index'));

    expect(Department::query()->find($department->id))->toBeNull()
        ->and(Department::withTrashed()->find($department->id))->not->toBeNull();
});

test('owner can complete a full employees crud cycle', function () {
    Storage::fake('custom');
    Mail::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $department = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Engineering',
        'code' => 'ENG',
    ]);

    $this->get(route('hr.employees.index'))
        ->assertOk()
        ->assertSee('الموظفون');

    $this->get(route('hr.employees.create'))->assertOk();

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $avatar = UploadedFile::fake()->createWithContent('avatar.png', $png);
    $cv = UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf');

    $this->post(route('hr.employees.store'), [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'national_id' => '1234567890',
        'phone' => '+966500000001',
        'address' => 'Riyadh',
        'job_title' => 'HR Specialist',
        'joining_date' => '2026-01-15',
        'status' => EmployeeStatus::Active->value,
        'department_id' => $department->id,
        'manager_id' => null,
        'avatar' => $avatar,
        'cv' => $cv,
        'create_user_account' => false,
        'auto_generate_password' => false,
    ])->assertRedirect();

    $employee = Employee::query()->where('first_name', 'Sara')->first();

    expect($employee)->not->toBeNull()
        ->and($employee->tenant_id)->toBe($user->tenant_id)
        ->and($employee->department_id)->toBe($department->id)
        ->and($employee->avatar_path)->not->toBeNull()
        ->and($employee->cv_path)->not->toBeNull();

    $this->get(route('hr.employees.show', $employee))
        ->assertOk()
        ->assertSee('Sara Ali')
        ->assertSee('HR Specialist');

    $this->get(route('hr.employees.index'))
        ->assertOk()
        ->assertSee('Sara Ali')
        ->assertSee('Engineering');

    $this->get(route('hr.departments.index'))
        ->assertOk()
        ->assertSee('1');

    $this->get(route('hr.employees.edit', $employee))->assertOk();

    $this->put(route('hr.employees.update', $employee), [
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'national_id' => '1234567890',
        'phone' => '+966500000001',
        'address' => 'Jeddah',
        'job_title' => 'Senior HR Specialist',
        'joining_date' => '2026-01-15',
        'status' => EmployeeStatus::OnLeave->value,
        'department_id' => $department->id,
        'manager_id' => null,
        'remove_avatar' => false,
        'remove_cv' => false,
        'create_user_account' => false,
        'auto_generate_password' => false,
    ])->assertRedirect(route('hr.employees.show', $employee));

    expect($employee->fresh()->job_title)->toBe('Senior HR Specialist')
        ->and($employee->fresh()->status)->toBe(EmployeeStatus::OnLeave)
        ->and($employee->fresh()->address)->toBe('Jeddah');

    $this->delete(route('hr.employees.destroy', $employee))
        ->assertRedirect(route('hr.employees.index'));

    expect(Employee::query()->find($employee->id))->toBeNull()
        ->and(Employee::withTrashed()->find($employee->id))->not->toBeNull();
});

test('employee create can sync a system user account', function () {
    Mail::fake();

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->post(route('hr.employees.store'), [
        'first_name' => 'Omar',
        'last_name' => 'Hassan',
        'phone' => '+966500000099',
        'job_title' => 'Accountant',
        'joining_date' => '2026-02-01',
        'status' => EmployeeStatus::Active->value,
        'create_user_account' => true,
        'auto_generate_password' => true,
        'email' => 'omar.hassan@example.test',
    ])->assertRedirect();

    $employee = Employee::query()->where('first_name', 'Omar')->first();

    expect($employee)->not->toBeNull()
        ->and($employee->user_id)->not->toBeNull();

    $linked = User::query()->find($employee->user_id);

    expect($linked)->not->toBeNull()
        ->and($linked->email)->toBe('omar.hassan@example.test')
        ->and($linked->tenant_id)->toBe($user->tenant_id)
        ->and($linked->hasRole(TenantPermissionCatalog::ROLE_EMPLOYEE))->toBeTrue();
});

test('hr manager can manage employees but cannot delete them', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Noura',
        'last_name' => 'Saleh',
        'job_title' => 'Recruiter',
    ]);

    expect($user->can('hr.employees.view_any'))->toBeTrue()
        ->and($user->can('hr.employees.create'))->toBeTrue()
        ->and($user->can('hr.employees.update'))->toBeTrue()
        ->and($user->can('hr.employees.delete'))->toBeFalse();

    $this->get(route('hr.employees.index'))
        ->assertOk()
        ->assertSee('Noura Saleh');

    $this->get(route('hr.employees.create'))->assertOk();
    $this->get(route('hr.employees.edit', $employee))->assertOk();
    $this->delete(route('hr.employees.destroy', $employee))->assertForbidden();

    expect($employee->fresh())->not->toBeNull();
});

test('employees and departments are isolated between tenants', function () {
    $tenantA = Tenant::factory()->active()->create();
    $tenantB = Tenant::factory()->active()->create();

    app(SeedDefaultTenantRoles::class)->handle($tenantA);
    app(SeedDefaultTenantRoles::class)->handle($tenantB);

    $deptA = Department::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Tenant A Dept',
        'code' => 'A1',
    ]);
    Department::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Secret Dept',
        'code' => 'B1',
    ]);

    $employeeA = Employee::factory()->create([
        'tenant_id' => $tenantA->id,
        'department_id' => $deptA->id,
        'first_name' => 'Alice',
        'last_name' => 'A',
    ]);
    Employee::factory()->create([
        'tenant_id' => $tenantB->id,
        'first_name' => 'Bob',
        'last_name' => 'Secret',
    ]);

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    app(TenantContext::class)->setTenant($tenantA);
    $userA->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    $this->actingAs($userA)
        ->get(route('hr.departments.index'))
        ->assertOk()
        ->assertSee('Tenant A Dept')
        ->assertDontSee('Tenant B Secret Dept');

    $this->actingAs($userA)
        ->get(route('hr.employees.index'))
        ->assertOk()
        ->assertSee('Alice A')
        ->assertDontSee('Bob Secret');

    $foreignEmployeeId = Employee::withoutGlobalScopes()
        ->where('tenant_id', $tenantB->id)
        ->value('id');

    $this->actingAs($userA)
        ->get('/app/hr/employees/'.$foreignEmployeeId)
        ->assertNotFound();

    $this->actingAs($userA)
        ->get(route('hr.employees.show', $employeeA))
        ->assertOk();
});
