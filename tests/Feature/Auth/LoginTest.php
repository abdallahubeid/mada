<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('the login page renders for guests', function () {
    $this->get('/login')
        ->assertOk()
        ->assertSee('أهلاً بعودتك');
});

test('authenticated users are redirected away from login', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->get('/login')
        ->assertRedirect();
});

test('a verified user on an active tenant can sign in and reaches the dashboard', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'password' => bcrypt('correct-password')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('dashboard'));

    $this->assertAuthenticatedAs($user);
});

test('an unverified user is redirected to the verification notice on sign in', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->unverified()->create(['tenant_id' => $tenant->id, 'password' => bcrypt('correct-password')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('verification.notice'));

    $this->assertAuthenticatedAs($user);
});

test('a verified user on a non-active tenant is redirected to the pending setup screen', function () {
    $tenant = Tenant::factory()->pendingApproval()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'password' => bcrypt('correct-password')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('dashboard.setup'));
});

test('a platform operator is redirected to the admin dashboard on sign in', function () {
    seedPlatformPermissions();

    $admin = User::factory()->create([
        'tenant_id' => null,
        'password' => 'correct-password',
    ]);
    PlatformPermissionCatalog::bindTeam();
    $admin->assignRole(PlatformPermissionCatalog::ROLE_SUPER_ADMIN);

    $this->post('/login', [
        'email' => $admin->email,
        'password' => 'correct-password',
    ])->assertRedirect(route('admin.dashboard'));

    $this->assertAuthenticatedAs($admin);
});

test('invalid credentials are rejected with a validation error', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id, 'password' => bcrypt('correct-password')]);

    $this->post('/login', [
        'email' => $user->email,
        'password' => 'wrong-password',
    ])->assertSessionHasErrors('email');

    $this->assertGuest();
});
