<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

beforeEach(function () {
    PlatformPermissionCatalog::bindTeam();
});

test('guest is redirected from admin dashboard to login', function () {
    $this->get(route('admin.dashboard'))
        ->assertRedirect(route('login'));
});

test('tenant user without platform role cannot access admin console', function () {
    seedPlatformPermissions();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->get(route('admin.dashboard'))
        ->assertForbidden();
});

test('content manager can view cms but is forbidden from tenants', function () {
    actingAsPlatformOperator(PlatformPermissionCatalog::ROLE_CONTENT_MANAGER);

    $this->get(route('admin.problems.index'))->assertOk();

    $this->get(route('admin.tenants'))->assertForbidden();
});

test('super admin bypasses permission checks via Gate before', function () {
    $admin = actingAsPlatformOperator(PlatformPermissionCatalog::ROLE_SUPER_ADMIN);

    expect($admin->isPlatformSuperAdmin())->toBeTrue()
        ->and($admin->can('tenants.view_any'))->toBeTrue()
        ->and($admin->can('roles.update'))->toBeTrue();

    $this->get(route('admin.dashboard'))->assertOk();
    $this->get(route('admin.tenants'))->assertOk();
    $this->get(route('admin.roles.index'))->assertOk();
    $this->get(route('admin.admins'))->assertOk();
});

test('user seeder creates a single owner super admin', function () {
    seedPlatformPermissions();
    $this->seed(UserSeeder::class);

    $users = User::query()->get();

    expect($users)->toHaveCount(1);

    $owner = $users->first();

    expect($owner->name)->toBe('Super Admin')
        ->and($owner->email)->toBe('owner@veyra.com')
        ->and($owner->email_verified_at)->not->toBeNull()
        ->and($owner->hasRole(PlatformPermissionCatalog::ROLE_SUPER_ADMIN))->toBeTrue();
});

test('new users are auto verified when email_verified_at is omitted', function () {
    $user = User::factory()->create([
        'email_verified_at' => null,
    ]);

    // Factory unverified() sets null explicitly — recreate without the key via query create.
    $auto = User::query()->create([
        'name' => 'Auto Verify',
        'email' => 'auto-verify@example.com',
        'password' => 'password',
    ]);

    expect($user->email_verified_at)->toBeNull()
        ->and($auto->email_verified_at)->not->toBeNull();
});

test('admin sidebar exposes users and roles dropdown links for super admin', function () {
    actingAsPlatformOperator();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('إدارة المستخدمين / المشرفين', false)
        ->assertSee('الأدوار والصلاحيات', false)
        ->assertSee(route('admin.admins'), false)
        ->assertSee(route('admin.roles.index'), false);
});

test('custom platform role grants console access login redirect and route permissions', function () {
    seedPlatformPermissions();

    $role = PlatformPermissionCatalog::withGlobalTeam(function () {
        $role = Role::query()->create([
            'name' => 'cms_only_editor',
            'guard_name' => PlatformPermissionCatalog::GUARD,
            'tenant_id' => null,
        ]);
        $role->syncPermissions(['cms.view_any', 'cms.create', 'account.profile.update']);

        return $role;
    });
    PlatformPermissionCatalog::forgetCachedPermissions();

    $user = User::factory()->create([
        'tenant_id' => null,
        'password' => 'correct-password',
        'email' => 'cms-editor@veyra.test',
    ]);

    PlatformPermissionCatalog::bindTeam();
    $user->assignRole($role->name);
    PlatformPermissionCatalog::forgetCachedPermissions();

    expect($user->fresh()->canAccessPlatformConsole())->toBeTrue()
        ->and($user->fresh()->preferredAdminHomeRoute())->toBe('admin.problems.index');

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('admin.problems.index'));

    $this->get(route('admin.problems.index'))->assertOk();
    $this->get(route('admin.tenants'))->assertForbidden();

    $html = $this->get(route('admin.problems.index'))->assertOk()->getContent();

    expect($html)->toContain('المشاكل')
        ->and($html)->not->toContain('إدارة المستأجرين');
});
