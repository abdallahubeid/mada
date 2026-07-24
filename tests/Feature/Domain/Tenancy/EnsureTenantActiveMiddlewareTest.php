<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Route;

uses(RefreshDatabase::class);

beforeEach(function () {
    Route::middleware(['web', 'tenant.active'])->get('/__test/tenant-gate', fn () => 'ok');
});

test('guests are rejected before the tenant is even resolved', function () {
    $this->get('/__test/tenant-gate')->assertUnauthorized();
});

test('a user without a tenant is forbidden', function () {
    $user = User::factory()->create(['tenant_id' => null]);

    $this->actingAs($user)
        ->get('/__test/tenant-gate')
        ->assertForbidden();
});

test('an active tenant is allowed through', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->get('/__test/tenant-gate')
        ->assertOk()
        ->assertSee('ok');
});

test('the tenant context is bound as a side effect of passing the gate', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)->get('/__test/tenant-gate');

    expect(app(TenantContext::class)->getTenantId())->toBe($tenant->id);
});

test('non-active tenant statuses are all rejected', function (TenantStatus $status) {
    $tenant = Tenant::factory()->create(['status' => $status]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->get('/__test/tenant-gate')
        ->assertForbidden();
})->with([
    'pending verification' => TenantStatus::PendingVerification,
    'pending approval' => TenantStatus::PendingApproval,
    'suspended' => TenantStatus::Suspended,
    'cancelled' => TenantStatus::Cancelled,
]);
