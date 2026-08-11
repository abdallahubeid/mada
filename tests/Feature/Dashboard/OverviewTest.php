<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login', function () {
    $this->get('/app/dashboard')->assertRedirect('/login');
});

test('a user on an active tenant can view the executive dashboard', function () {
    actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        ['status' => TenantStatus::Active->value, 'name' => 'Acme Robotics'],
        ['name' => 'Jane Owner'],
    );

    $this->get('/app/dashboard')
        ->assertOk()
        ->assertSee('Jane Owner')
        ->assertSee('Acme Robotics')
        ->assertSee('إجمالي الموظفين')
        ->assertSee('نسبة الحضور الشهرية');
});

test('a user on a non-active tenant is forbidden from the dashboard', function (TenantStatus $status) {
    actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        ['status' => $status->value],
    );

    $this->get('/app/dashboard')
        ->assertForbidden();
})->with([
    'pending verification' => TenantStatus::PendingVerification,
    'pending approval' => TenantStatus::PendingApproval,
    'suspended' => TenantStatus::Suspended,
    'cancelled' => TenantStatus::Cancelled,
]);

test('an active tenant user without dashboard permission cannot view the dashboard', function () {
    $user = actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        ['status' => TenantStatus::Active->value],
    );

    $user->syncRoles([]);

    $this->get('/app/dashboard')->assertForbidden();
});
