<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;

uses(RefreshDatabase::class);

test('roles index shows sequence users count and create action', function () {
    actingAsPlatformOperator();

    $this->get(route('admin.roles.index'))
        ->assertOk()
        ->assertSee('عدد المستخدمين', false)
        ->assertSee('إنشاء دور جديد', false)
        ->assertSee('عدد الصلاحيات', false)
        ->assertSee('>1</td>', false);
});

test('super admin can create a custom role with permissions', function () {
    actingAsPlatformOperator();

    $this->post(route('admin.roles.store'), [
        'name' => 'marketing_editor',
        'permissions' => ['dashboard.view', 'cms.view_any'],
    ])->assertRedirect(route('admin.roles.index'));

    $role = Role::query()->where('name', 'marketing_editor')->whereNull('tenant_id')->first();

    expect($role)->not->toBeNull()
        ->and($role->permissions->pluck('name')->all())->toContain('dashboard.view', 'cms.view_any');
});

test('protected system roles cannot be deleted', function () {
    actingAsPlatformOperator();

    $role = Role::query()
        ->where('name', PlatformPermissionCatalog::ROLE_CONTENT_MANAGER)
        ->whereNull('tenant_id')
        ->firstOrFail();

    $this->from(route('admin.roles.index'))
        ->delete(route('admin.roles.destroy', $role))
        ->assertRedirect();

    expect(Role::query()->whereKey($role->id)->exists())->toBeTrue();
});

test('custom role without users can be deleted', function () {
    actingAsPlatformOperator();

    $role = Role::query()->create([
        'name' => 'temp_ops',
        'guard_name' => PlatformPermissionCatalog::GUARD,
        'tenant_id' => null,
    ]);

    $this->delete(route('admin.roles.destroy', $role))
        ->assertRedirect(route('admin.roles.index'));

    expect(Role::query()->whereKey($role->id)->exists())->toBeFalse();
});

test('admins index shows sequence and delete admin action', function () {
    actingAsPlatformOperator();

    $other = User::factory()->create(['tenant_id' => null, 'name' => 'Other Admin']);
    PlatformPermissionCatalog::bindTeam();
    $other->assignRole(PlatformPermissionCatalog::ROLE_CONTENT_MANAGER);

    $this->get(route('admin.admins'))
        ->assertOk()
        ->assertSee('>1</td>', false)
        ->assertSee('الصورة', false)
        ->assertSee('حذف مشرف', false)
        ->assertSee('>O</span>', false);
});

test('admins index shows uploaded avatar thumbnail when present', function () {
    Storage::fake('custom');

    actingAsPlatformOperator();

    $other = User::factory()->create([
        'tenant_id' => null,
        'name' => 'Avatar Admin',
        'email' => 'avatar-admin@veyra.test',
    ]);
    PlatformPermissionCatalog::bindTeam();
    $other->assignRole(PlatformPermissionCatalog::ROLE_SUPPORT_AGENT);

    $path = 'user/avatar/admins-table.jpg';
    Storage::disk('custom')->put($path, 'avatar-bytes');

    $other->images()->create([
        'collection' => 'avatar',
        'disk' => 'custom',
        'path' => $path,
        'original_name' => 'admins-table.jpg',
        'mime_type' => 'image/jpeg',
        'file_size' => 12,
        'sort_order' => 0,
    ]);

    $avatarUrl = $other->fresh()->load('avatar')->avatar_url;

    $this->get(route('admin.admins'))
        ->assertOk()
        ->assertSee($avatarUrl, false)
        ->assertSee('h-10 w-10 rounded-full object-cover', false);
});

test('admin create form embeds role permissions map for alpine preselection', function () {
    actingAsPlatformOperator();

    $html = $this->get(route('admin.admins.create'))
        ->assertOk()
        ->assertSee('roleMap:', false)
        ->assertSee('content_manager', false)
        ->assertSee('cms.view_any', false)
        ->getContent();

    expect($html)->toContain('applyRole()')
        ->and($html)->toContain('x-model="selected"');
});

test('admin soft deletes via destroy action', function () {
    actingAsPlatformOperator();

    $other = User::factory()->create([
        'tenant_id' => null,
        'name' => 'Disposable Admin',
        'email' => 'disposable@veyra.test',
    ]);
    PlatformPermissionCatalog::bindTeam();
    $other->assignRole(PlatformPermissionCatalog::ROLE_SUPPORT_AGENT);

    $this->delete(route('admin.admins.destroy', $other))
        ->assertRedirect(route('admin.admins'));

    $this->assertSoftDeleted('users', ['id' => $other->id]);
});
