<?php

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('guests are redirected away from the setup wizard', function () {
    $this->get(route('dashboard.setup'))->assertRedirect(route('login'));
});

test('an active tenant owner is redirected from setup to the dashboard', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('dashboard.setup'))
        ->assertRedirect(route('dashboard'));
});

test('a pending-approval owner can view the setup wizard', function () {
    $user = actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        ['status' => 'pending_approval', 'name' => 'Acme Robotics'],
    );

    $this->get(route('dashboard.setup'))
        ->assertOk()
        ->assertSee('إعداد مؤسستك')
        ->assertSee('Acme Robotics');

    expect($user->can('tenant.settings.update'))->toBeTrue();
});

test('an hr manager cannot open or submit the setup wizard', function () {
    actingAsTenantUser(
        TenantPermissionCatalog::ROLE_HR_MANAGER,
        ['status' => 'pending_approval'],
    );

    $this->get(route('dashboard.setup'))->assertForbidden();

    $this->put(route('dashboard.setup.update'), [
        'password' => 'NewPass123',
        'password_confirmation' => 'NewPass123',
        'currency' => 'SAR',
        'timezone' => 'Asia/Riyadh',
        'working_days' => [0, 1, 2, 3, 4],
    ])->assertForbidden();
});

test('owner can complete the setup wizard and persist org settings and work calendar', function () {
    Storage::fake('custom');

    $user = actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        ['status' => 'pending_approval'],
    );

    $png = base64_decode('iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAYAAAAfFcSJAAAADUlEQVR42mP8z8BQDwAEhQGAhKmMIQAAAABJRU5ErkJggg==');
    $logo = UploadedFile::fake()->createWithContent('logo.png', $png);

    $response = $this->put(route('dashboard.setup.update'), [
        'password' => 'SecurePass99',
        'password_confirmation' => 'SecurePass99',
        'logo' => $logo,
        'currency' => 'AED',
        'timezone' => 'Asia/Dubai',
        'working_days' => [0, 1, 2, 3, 4],
        'holidays' => [
            ['date' => '2026-09-23', 'name' => 'National Day'],
            ['date' => '', 'name' => ''],
        ],
    ]);

    $response
        ->assertRedirect(route('dashboard.setup'))
        ->assertSessionHas('flasher');

    $user->refresh();
    expect(Hash::check('SecurePass99', $user->password))->toBeTrue();

    $settings = OrgSetting::query()->first();
    expect($settings)->not->toBeNull()
        ->and($settings->currency)->toBe('AED')
        ->and($settings->timezone)->toBe('Asia/Dubai')
        ->and($settings->setup_completed_at)->not->toBeNull()
        ->and($settings->tenant_id)->toBe($user->tenant_id);

    $calendar = WorkCalendar::query()->first();
    expect($calendar)->not->toBeNull()
        ->and($calendar->name)->toBe('Default')
        ->and($calendar->working_days)->toBe([0, 1, 2, 3, 4])
        ->and($calendar->holidays)->toBe([
            ['date' => '2026-09-23', 'name' => 'National Day'],
        ]);

    expect($user->tenant->images()->where('collection', 'logo')->count())->toBe(1);
});

test('owner receives role-management permissions while hr manager does not', function () {
    $owner = actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        ['status' => 'active'],
    );

    expect($owner->can('tenant.roles.manage'))->toBeTrue()
        ->and($owner->can('tenant.users.manage'))->toBeTrue()
        ->and($owner->can('hr.departments.view_any'))->toBeTrue();

    $tenant = Tenant::factory()->active()->create();
    app(SeedDefaultTenantRoles::class)->handle($tenant);

    $hr = User::factory()->create(['tenant_id' => $tenant->id]);
    app(TenantContext::class)->setTenant($tenant);
    $hr->assignRole(TenantPermissionCatalog::ROLE_HR_MANAGER);

    expect($hr->can('tenant.roles.manage'))->toBeFalse()
        ->and($hr->can('tenant.users.manage'))->toBeFalse()
        ->and($hr->can('tenant.settings.update'))->toBeFalse()
        ->and($hr->can('hr.departments.view_any'))->toBeTrue()
        ->and($hr->can('hr.departments.update'))->toBeTrue()
        ->and($hr->can('hr.departments.create'))->toBeFalse()
        ->and($hr->can('hr.departments.delete'))->toBeFalse()
        ->and($hr->can('tenant.dashboard.view'))->toBeTrue();
});
