<?php

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can complete a full departments crud cycle', function () {
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
    ])
        ->assertRedirect(route('hr.departments.index'))
        ->assertSessionHas('flasher');

    $department = Department::query()->where('code', 'HR')->first();

    expect($department)->not->toBeNull()
        ->and($department->tenant_id)->toBe($user->tenant_id)
        ->and($department->name)->toBe('Human Resources');

    $this->get(route('hr.departments.edit', $department))->assertOk();

    $this->put(route('hr.departments.update', $department), [
        'name' => 'People',
        'code' => 'PEO',
        'description' => 'Updated',
        'parent_id' => null,
    ])->assertRedirect(route('hr.departments.index'));

    expect($department->fresh()->name)->toBe('People');

    $this->delete(route('hr.departments.destroy', $department))
        ->assertRedirect(route('hr.departments.index'));

    expect(Department::query()->find($department->id))->toBeNull()
        ->and(Department::withTrashed()->find($department->id))->not->toBeNull();
});

test('hr manager can view and update departments but cannot create or delete', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $department = Department::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Operations',
        'code' => 'OPS',
    ]);

    expect($user->can('hr.departments.view_any'))->toBeTrue()
        ->and($user->can('hr.departments.update'))->toBeTrue()
        ->and($user->can('hr.departments.create'))->toBeFalse()
        ->and($user->can('hr.departments.delete'))->toBeFalse();

    $this->get(route('hr.departments.index'))
        ->assertOk()
        ->assertSee('Operations')
        ->assertDontSee('إضافة قسم');

    $this->get(route('hr.departments.create'))->assertForbidden();
    $this->post(route('hr.departments.store'), [
        'name' => 'Blocked',
        'code' => 'BLK',
    ])->assertForbidden();

    $this->get(route('hr.departments.edit', $department))->assertOk();

    $this->put(route('hr.departments.update', $department), [
        'name' => 'Operations Updated',
        'code' => 'OPS',
        'description' => null,
        'parent_id' => null,
    ])->assertRedirect(route('hr.departments.index'));

    expect($department->fresh()->name)->toBe('Operations Updated');

    $this->delete(route('hr.departments.destroy', $department))->assertForbidden();
    expect($department->fresh())->not->toBeNull();
});

test('departments are isolated between tenants', function () {
    $tenantA = Tenant::factory()->active()->create();
    $tenantB = Tenant::factory()->active()->create();

    app(SeedDefaultTenantRoles::class)->handle($tenantA);
    app(SeedDefaultTenantRoles::class)->handle($tenantB);

    $deptA = Department::factory()->create([
        'tenant_id' => $tenantA->id,
        'name' => 'Tenant A Only',
        'code' => 'A1',
    ]);
    Department::factory()->create([
        'tenant_id' => $tenantB->id,
        'name' => 'Tenant B Secret',
        'code' => 'B1',
    ]);

    $userA = User::factory()->create(['tenant_id' => $tenantA->id]);
    app(TenantContext::class)->setTenant($tenantA);
    $userA->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    $this->actingAs($userA)
        ->get(route('hr.departments.index'))
        ->assertOk()
        ->assertSee('Tenant A Only')
        ->assertDontSee('Tenant B Secret');

    $this->actingAs($userA)
        ->get(route('hr.departments.edit', $deptA))
        ->assertOk();

    $foreignId = Department::withoutGlobalScopes()
        ->where('tenant_id', $tenantB->id)
        ->value('id');

    $this->actingAs($userA)
        ->get('/app/hr/departments/'.$foreignId.'/edit')
        ->assertNotFound();
});
