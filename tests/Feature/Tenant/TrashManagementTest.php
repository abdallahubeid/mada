<?php

use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('soft deleting a record moves it to the tenant trash', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'سارة',
        'last_name' => 'أحمد',
    ]);

    $this->delete(route('hr.employees.destroy', $employee))
        ->assertRedirect(route('hr.employees.index'));

    $flasher = session('flasher');

    expect($flasher)->toBeArray()
        ->and($flasher['undo_url'] ?? null)->toBe(route('tenant.trash.restore', [
            'type' => 'employees',
            'id' => $employee->id,
        ]))
        ->and($flasher['undo_label'] ?? null)->toBe('تراجع');

    $this->get(route('tenant.trash.index'))
        ->assertOk()
        ->assertSee('سلة المحذوفات', false)
        ->assertSee('سارة أحمد', false)
        ->assertSee('الموظفين', false);

    $this->get(route('tenant.trash.index', ['type' => 'employees']))
        ->assertOk()
        ->assertSee('سارة أحمد', false);
});

test('restoring a soft deleted record makes it active again', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $department = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'قسم للاستعادة',
    ]);
    $department->delete();

    $this->post(route('tenant.trash.restore', [
        'type' => 'departments',
        'id' => $department->id,
    ]))->assertRedirect();

    expect(Department::query()->find($department->id))->not->toBeNull()
        ->and($department->fresh()->trashed())->toBeFalse();

    $this->get(route('tenant.trash.index'))
        ->assertOk()
        ->assertDontSee('قسم للاستعادة', false);
});

test('force deleting permanently purges the record', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'نهائي',
        'last_name' => 'حذف',
    ]);
    $employee->delete();

    $this->delete(route('tenant.trash.force-delete', [
        'type' => 'employees',
        'id' => $employee->id,
    ]))->assertRedirect(route('tenant.trash.index'));

    expect(Employee::withTrashed()->find($employee->id))->toBeNull();
});

test('empty trash permanently removes soft deleted items for the tenant', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $first = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'أول محذوف',
    ]);
    $second = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'ثاني محذوف',
    ]);

    $first->delete();
    $second->delete();

    $this->delete(route('tenant.trash.empty'))
        ->assertRedirect(route('tenant.trash.index'));

    expect(Department::withTrashed()->find($first->id))->toBeNull()
        ->and(Department::withTrashed()->find($second->id))->toBeNull();
});

test('employee without trash permission cannot open the trash', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.trash.index'))->assertForbidden();
});
