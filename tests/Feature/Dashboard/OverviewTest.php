<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Livewire\Dashboard\Overview;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('guests are redirected to login', function () {
    $this->get('/app/dashboard')->assertRedirect('/login');
});

test('a user on an active tenant can view the dashboard shell', function () {
    $tenant = Tenant::factory()->active()->create(['name' => 'Acme Robotics']);
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'name' => 'Jane Owner']);

    $this->actingAs($user)
        ->get('/app/dashboard')
        ->assertOk()
        ->assertSeeLivewire(Overview::class)
        ->assertSee('Jane Owner')
        ->assertSee('Acme Robotics');
});

test('a user on a non-active tenant is forbidden from the dashboard', function (TenantStatus $status) {
    $tenant = Tenant::factory()->create(['status' => $status]);
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->get('/app/dashboard')
        ->assertForbidden();
})->with([
    'pending verification' => TenantStatus::PendingVerification,
    'pending approval' => TenantStatus::PendingApproval,
    'suspended' => TenantStatus::Suspended,
    'cancelled' => TenantStatus::Cancelled,
]);
