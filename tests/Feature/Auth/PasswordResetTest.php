<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Auth\Notifications\ResetPassword;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Password;

uses(RefreshDatabase::class);

test('forgot password page renders for guests', function () {
    $this->get(route('password.request'))
        ->assertOk()
        ->assertSee('نسيت كلمة المرور؟', false)
        ->assertSee('إرسال رابط إعادة التعيين', false);
});

test('login page links to the named forgot password route', function () {
    $this->get(route('login'))
        ->assertOk()
        ->assertSee(route('password.request'), false);
});

test('forgot password sends a reset link notification', function () {
    Notification::fake();

    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'reset-me@veyra.test',
    ]);

    $this->post(route('password.email'), [
        'email' => $user->email,
    ])->assertRedirect()
        ->assertSessionHas('status');

    Notification::assertSentTo($user, ResetPassword::class);
});

test('user can reset password with a valid token', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => 'recover@veyra.test',
        'password' => Hash::make('OldSecret123'),
    ]);

    $token = Password::broker()->createToken($user);

    $this->get(route('password.reset', [
        'token' => $token,
        'email' => $user->email,
    ]))->assertOk()
        ->assertSee('تعيين كلمة مرور جديدة', false);

    $this->post(route('password.update'), [
        'token' => $token,
        'email' => $user->email,
        'password' => 'NewSecret123',
        'password_confirmation' => 'NewSecret123',
    ])->assertRedirect(route('login'));

    expect(Hash::check('NewSecret123', $user->fresh()->password))->toBeTrue();
});
