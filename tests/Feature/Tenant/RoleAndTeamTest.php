<?php

use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Mail\Tenancy\EmployeeWelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('tenant permission catalog sync creates missing web permissions', function () {
    Permission::query()
        ->where('name', 'tenant.users.manage')
        ->where('guard_name', TenantPermissionCatalog::GUARD)
        ->delete();

    expect(Permission::query()->where('name', 'tenant.users.manage')->exists())->toBeFalse();

    TenantPermissionCatalog::syncCatalog();

    expect(Permission::query()
        ->where('name', 'tenant.users.manage')
        ->where('guard_name', TenantPermissionCatalog::GUARD)
        ->exists())->toBeTrue();

    foreach (TenantPermissionCatalog::all() as $permission) {
        expect(Permission::query()
            ->where('name', $permission)
            ->where('guard_name', TenantPermissionCatalog::GUARD)
            ->exists())->toBeTrue();
    }
});

test('owner can manage custom roles and permissions', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('roles.index'))
        ->assertOk()
        ->assertSee('الأدوار والصلاحيات');

    $this->post(route('roles.store'), [
        'name' => 'Custom Ops',
        'permissions' => [
            'tenant.dashboard.view',
            'hr.departments.view_any',
        ],
    ])
        ->assertRedirect(route('roles.index'))
        ->assertSessionHas('flasher');

    $role = Role::query()->where('name', 'Custom Ops')->first();
    expect($role)->not->toBeNull()
        ->and($role->tenant_id)->not->toBeNull()
        ->and($role->permissions->pluck('name')->sort()->values()->all())
        ->toBe(['hr.departments.view_any', 'tenant.dashboard.view']);

    $this->put(route('roles.update', $role), [
        'name' => 'Custom Ops',
        'permissions' => ['tenant.dashboard.view'],
    ])->assertRedirect(route('roles.index'));

    expect($role->fresh()->permissions->pluck('name')->all())->toBe(['tenant.dashboard.view']);

    $this->delete(route('roles.destroy', $role))
        ->assertRedirect(route('roles.index'));

    expect(Role::query()->where('name', 'Custom Ops')->exists())->toBeFalse();
});

test('owner cannot delete protected system roles', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $ownerRole = Role::query()
        ->where('name', TenantPermissionCatalog::ROLE_OWNER)
        ->where('tenant_id', $user->tenant_id)
        ->firstOrFail();

    $this->delete(route('roles.destroy', $ownerRole))
        ->assertRedirect();

    expect(Role::query()->whereKey($ownerRole->id)->exists())->toBeTrue();
});

test('hr manager receives 403 on role management', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $this->get(route('roles.index'))->assertForbidden();
    $this->get(route('roles.create'))->assertForbidden();
    $this->post(route('roles.store'), [
        'name' => 'Blocked',
        'permissions' => ['tenant.dashboard.view'],
    ])->assertForbidden();
});

test('owner can create a team member with role and department', function () {
    Mail::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $department = Department::query()->create([
        'name' => 'الموارد البشرية',
        'code' => 'HR',
    ]);

    $this->get(route('team.create'))
        ->assertOk()
        ->assertSee('إضافة عضو للفريق')
        ->assertSee('صلاحيات مباشرة (تُحدّث تلقائياً حسب الدور)')
        ->assertSee('إدارة أعضاء الفريق');

    $this->get(route('team.index'))
        ->assertOk()
        ->assertSee('إدارة الوصول والصلاحيات')
        ->assertSee('أعضاء الفريق')
        ->assertSee('الأدوار والصلاحيات');

    $this->post(route('team.store'), [
        'name' => 'سارة أحمد',
        'email' => 'sara@acme.test',
        'department_id' => $department->id,
        'role' => TenantPermissionCatalog::ROLE_EMPLOYEE,
        'permissions' => [
            'tenant.dashboard.view',
            'hr.departments.view_any',
        ],
        'auto_generate_password' => '1',
        'password' => null,
        'password_confirmation' => null,
    ])
        ->assertRedirect(route('team.index'))
        ->assertSessionHas('flasher');

    $member = User::query()->where('email', 'sara@acme.test')->first();

    expect($member)->not->toBeNull()
        ->and($member->tenant_id)->toBe($owner->tenant_id)
        ->and($member->department_id)->toBe($department->id)
        ->and($member->is_active)->toBeTrue()
        ->and($member->hasRole(TenantPermissionCatalog::ROLE_EMPLOYEE))->toBeTrue()
        ->and($member->getDirectPermissions()->pluck('name')->sort()->values()->all())
        ->toBe(['hr.departments.view_any', 'tenant.dashboard.view']);

    Mail::assertSent(EmployeeWelcomeMail::class, function (EmployeeWelcomeMail $mail) use ($member): bool {
        return $mail->user->is($member) && filled($mail->plainPassword);
    });
});

test('owner can update and delete a team member', function () {
    Mail::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $department = Department::query()->create([
        'name' => 'التقنية',
        'code' => 'IT',
    ]);

    $member = User::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'name' => 'عضو قديم',
        'email' => 'old@acme.test',
        'is_active' => true,
    ]);
    $member->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);

    $this->put(route('team.update', $member), [
        'name' => 'عضو محدّث',
        'email' => 'updated@acme.test',
        'department_id' => $department->id,
        'role' => TenantPermissionCatalog::ROLE_HR_MANAGER,
        'permissions' => [
            'tenant.dashboard.view',
            'tenant.settings.view',
            'hr.departments.view_any',
        ],
        'reset_password' => '0',
        'auto_generate_password' => '0',
    ])
        ->assertRedirect(route('team.index'))
        ->assertSessionHas('flasher');

    $member->refresh();

    expect($member->name)->toBe('عضو محدّث')
        ->and($member->email)->toBe('updated@acme.test')
        ->and($member->department_id)->toBe($department->id)
        ->and($member->hasRole(TenantPermissionCatalog::ROLE_HR_MANAGER))->toBeTrue()
        ->and($member->hasRole(TenantPermissionCatalog::ROLE_EMPLOYEE))->toBeFalse()
        ->and($member->getDirectPermissions()->pluck('name')->sort()->values()->all())
        ->toBe(['hr.departments.view_any', 'tenant.dashboard.view', 'tenant.settings.view']);

    Mail::assertNothingSent();

    $this->delete(route('team.destroy', $member))
        ->assertRedirect(route('team.index'))
        ->assertSessionHas('flasher');

    expect(User::query()->whereKey($member->id)->exists())->toBeFalse()
        ->and(User::withTrashed()->whereKey($member->id)->exists())->toBeTrue();
});

test('guard prevents deactivating or deleting the sole active owner', function () {
    $ownerA = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $ownerB = User::factory()->create([
        'tenant_id' => $ownerA->tenant_id,
        'is_active' => true,
    ]);
    $ownerB->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    $this->actingAs($ownerB)
        ->patch(route('team.toggle-status', $ownerA))
        ->assertRedirect(route('team.index'));

    expect($ownerA->fresh()->is_active)->toBeFalse();

    $this->actingAs($ownerB)
        ->patch(route('team.toggle-status', $ownerB))
        ->assertRedirect();

    expect($ownerB->fresh()->is_active)->toBeTrue();

    $this->actingAs($ownerB)
        ->delete(route('team.destroy', $ownerB))
        ->assertRedirect();

    expect(User::query()->whereKey($ownerB->id)->exists())->toBeTrue();
});

test('hr manager cannot access team management', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $this->get(route('team.index'))->assertForbidden();
    $this->get(route('team.create'))->assertForbidden();
});

test('tenant owner bypasses permission checks via Gate before', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $ownerRole = Role::query()
        ->where('name', TenantPermissionCatalog::ROLE_OWNER)
        ->where('tenant_id', $owner->tenant_id)
        ->firstOrFail();

    // Wipe every pivot row — Owner must still authorize via Gate::before alone.
    $ownerRole->syncPermissions([]);
    app(PermissionRegistrar::class)->forgetCachedPermissions();
    $owner->unsetRelation('roles');
    $owner->unsetRelation('permissions');

    expect($owner->isTenantOwner())->toBeTrue()
        ->and($owner->isOwner())->toBeTrue()
        ->and($owner->can('tenant.dashboard.view'))->toBeTrue()
        ->and($owner->can('tenant.users.manage'))->toBeTrue()
        ->and($owner->can('hr.departments.delete'))->toBeTrue()
        ->and($owner->can('tenant.roles.manage'))->toBeTrue()
        ->and($owner->can('hr.assets.manage'))->toBeTrue()
        ->and($owner->can('future.module.brand_new_ability'))->toBeTrue();

    $this->get(route('dashboard'))->assertOk();
    $this->get(route('team.index'))->assertOk();
    $this->get(route('settings.company'))->assertOk();
    $this->get(route('roles.index'))->assertOk();
    $this->get(route('tenant.assets.index'))->assertOk();
    $this->get(route('tenant.contact-messages.index'))->assertOk();
});

test('non-owner tenant roles do not receive Gate before bypass', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    expect($hr->isTenantOwner())->toBeFalse()
        ->and($hr->isOwner())->toBeFalse()
        ->and($hr->can('tenant.users.manage'))->toBeFalse()
        ->and($hr->can('tenant.roles.manage'))->toBeFalse()
        ->and($hr->can('future.module.brand_new_ability'))->toBeFalse();

    // /app/dashboard dispatches by role, so an HR Manager is redirected to the
    // HR dashboard. Following the redirect keeps the original intent: they are
    // not denied, they just land on their own dashboard.
    $this->followingRedirects()->get(route('dashboard'))->assertOk();
});
