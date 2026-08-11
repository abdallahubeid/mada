<?php

use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can view and update company settings', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('settings.company'))
        ->assertOk()
        ->assertSee('إعدادات المؤسسة');

    $this->put(route('settings.company.update'), [
        'currency' => 'USD',
        'timezone' => 'UTC',
        'evaluation_periodicity' => 'quarterly',
        'working_days' => [1, 2, 3, 4, 5],
        'holidays' => [
            ['date' => '2026-01-01', 'name' => 'New Year'],
        ],
    ])
        ->assertRedirect(route('settings.company'))
        ->assertSessionHas('flasher');

    $settings = OrgSetting::query()->first();
    expect($settings)->not->toBeNull()
        ->and($settings->currency)->toBe('USD')
        ->and($settings->timezone)->toBe('UTC');

    $calendar = WorkCalendar::query()->first();
    expect($calendar)->not->toBeNull()
        ->and($calendar->working_days)->toBe([1, 2, 3, 4, 5])
        ->and($calendar->holidays)->toBe([
            ['date' => '2026-01-01', 'name' => 'New Year'],
        ]);
});

test('hr manager can view company settings but cannot update them', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $this->get(route('settings.company'))
        ->assertOk()
        ->assertSee('عرض فقط');

    $this->put(route('settings.company.update'), [
        'currency' => 'EUR',
        'timezone' => 'UTC',
        'evaluation_periodicity' => 'monthly',
        'working_days' => [1, 2, 3],
    ])->assertForbidden();

    expect(OrgSetting::query()->count())->toBe(0);
});
