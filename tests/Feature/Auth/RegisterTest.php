<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

function validRegistrationPayload(array $overrides = []): array
{
    return array_merge([
        'name' => 'Jane Owner',
        'email' => 'jane@acme.test',
        'password' => 'Password123',
        'password_confirmation' => 'Password123',
        'company_name' => 'Acme Robotics',
        'company_slug' => 'acme-robotics',
        'industry' => 'technology',
        'team_size' => '11-50',
        'plan' => 'growth',
        'terms' => '1',
    ], $overrides);
}

test('the registration page renders for guests', function () {
    $this->get('/register')
        ->assertOk()
        ->assertSee('ابدأ تجربتك المجانية');
});

test('a new tenant and its owner user can be registered', function () {
    Notification::fake();

    $response = $this->post('/register', validRegistrationPayload());

    $response->assertRedirect(route('verification.notice'));

    $tenant = Tenant::query()->where('slug', 'acme-robotics')->first();

    expect($tenant)->not->toBeNull();
    expect($tenant->status)->toBe(TenantStatus::PendingVerification);
    expect($tenant->industry)->toBe('technology');
    expect($tenant->team_size)->toBe('11-50');
    expect($tenant->plan)->toBe('growth');

    $user = User::query()->where('email', 'jane@acme.test')->first();

    expect($user)->not->toBeNull();
    expect($user->tenant_id)->toBe($tenant->id);
    expect($user->hasVerifiedEmail())->toBeFalse();
    expect($user->hasRole('Owner'))->toBeTrue();

    $this->assertAuthenticatedAs($user);

    Notification::assertSentTo($user, VerifyEmail::class);
});

test('registration requires a unique company slug', function () {
    Tenant::factory()->create(['slug' => 'acme-robotics']);

    $this->post('/register', validRegistrationPayload())
        ->assertSessionHasErrors('company_slug');

    $this->assertGuest();
});

test('registration requires a unique email', function () {
    $tenant = Tenant::factory()->active()->create();
    User::factory()->create(['tenant_id' => $tenant->id, 'email' => 'jane@acme.test']);

    $this->post('/register', validRegistrationPayload())
        ->assertSessionHasErrors('email');

    $this->assertGuest();
});

test('registration requires the terms to be accepted', function () {
    $this->post('/register', validRegistrationPayload(['terms' => null]))
        ->assertSessionHasErrors('terms');

    $this->assertGuest();
});

test('registration rejects a malformed company slug', function () {
    $this->post('/register', validRegistrationPayload(['company_slug' => 'Not A Slug!']))
        ->assertSessionHasErrors('company_slug');

    $this->assertGuest();
});
