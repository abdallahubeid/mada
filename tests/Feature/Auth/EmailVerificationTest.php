<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Events\Verified;
use Illuminate\Auth\Notifications\VerifyEmail;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

test('guests cannot view the verification notice', function () {
    $this->get('/verify-email')->assertRedirect('/login');
});

test('an unverified user sees the verification notice', function () {
    $tenant = Tenant::factory()->create();
    $user = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->get('/verify-email')
        ->assertOk()
        ->assertSee('تحقق من بريدك الإلكتروني');
});

test('an already-verified user is redirected away from the verification notice', function () {
    $tenant = Tenant::factory()->pendingApproval()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->get('/verify-email')
        ->assertRedirect(route('dashboard.setup'));
});

test('a valid signed link verifies the email and redirects to the pending setup screen', function () {
    Event::fake();

    $tenant = Tenant::factory()->create(['status' => TenantStatus::PendingVerification]);
    $user = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

    $url = URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);

    $this->actingAs($user)
        ->get($url)
        ->assertRedirect(route('dashboard.setup'));

    expect($user->refresh()->hasVerifiedEmail())->toBeTrue();

    Event::assertDispatched(Verified::class);
});

test('the resend button dispatches a fresh verification notification', function () {
    Notification::fake();

    $tenant = Tenant::factory()->create();
    $user = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($user)
        ->post('/verify-email/resend')
        ->assertRedirect()
        ->assertSessionHas('status', 'verification-link-sent');

    Notification::assertSentTo($user, VerifyEmail::class);
});
