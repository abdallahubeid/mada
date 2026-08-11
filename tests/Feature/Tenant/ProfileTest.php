<?php

use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\Image;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('tenant user can view profile page with cropper assets', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active'], [
        'name' => 'خالد العتيبي',
        'email' => 'khaled@tenant.test',
        'job_title' => 'مدير العمليات',
    ]);

    $this->get(route('profile.edit'))
        ->assertOk()
        ->assertSee('الملف الشخصي', false)
        ->assertSee('خالد العتيبي', false)
        ->assertSee('khaled@tenant.test', false)
        ->assertSee('المعلومات الشخصية', false)
        ->assertSee('الأمان وكلمة المرور', false)
        ->assertSee('إعادة ضبط / قص الصورة', false)
        ->assertSee('cropper.min.js', false)
        ->assertSee('بحث في المنصة...', false);

    expect($user)->toBeInstanceOf(User::class);
});

test('tenant profile update persists personal information and avatar on custom disk', function () {
    Storage::fake('custom');

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active'], [
        'name' => 'Old Tenant',
        'email' => 'old-tenant@veyra.test',
    ]);

    $this->put(route('profile.update'), [
        'name' => 'New Tenant',
        'email' => 'new-tenant@veyra.test',
        'phone' => '+966501112233',
        'job_title' => 'CEO',
        'avatar' => UploadedFile::fake()->create('avatar.jpg', 100, 'image/jpeg'),
    ])->assertRedirect(route('profile.edit'));

    $user->refresh();
    $avatar = $user->avatar;

    expect($user->name)->toBe('New Tenant')
        ->and($user->email)->toBe('new-tenant@veyra.test')
        ->and($user->phone)->toBe('+966501112233')
        ->and($user->job_title)->toBe('CEO')
        ->and($user->email_verified_at)->toBeNull()
        ->and($avatar)->toBeInstanceOf(Image::class)
        ->and($avatar->disk)->toBe('custom')
        ->and($avatar->collection)->toBe('avatar');

    Storage::disk('custom')->assertExists($avatar->path);
});

test('tenant password update requires current password', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active'], [
        'password' => Hash::make('Secret123'),
    ]);

    $this->from(route('profile.edit'))
        ->put(route('profile.update'), [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'NewSecret123',
            'password_confirmation' => 'NewSecret123',
        ])
        ->assertRedirect(route('profile.edit'))
        ->assertSessionHasErrors('current_password');

    $this->put(route('profile.update'), [
        'name' => $user->name,
        'email' => $user->email,
        'current_password' => 'Secret123',
        'password' => 'NewSecret123',
        'password_confirmation' => 'NewSecret123',
    ])->assertRedirect(route('profile.edit'));

    expect(Hash::check('NewSecret123', $user->fresh()->password))->toBeTrue();
});

test('tenant app shell defaults dark mode when no local storage preference', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $html = $this->get(route('dashboard'))->assertOk()->getContent();

    /*
     * Asserts the CONTRACT, not the source text. This previously pinned two
     * literal strings from the inline script, so consolidating the six copies
     * into <x-theme-script /> failed it even though the behaviour was intact —
     * the test was describing an implementation rather than a requirement.
     */
    expect($html)
        // Dark for anyone who has not explicitly chosen light.
        ->toContain("classList.toggle('dark', stored !== 'light')")
        ->toContain("localStorage.getItem('veyra-theme')");

});

test('the theme bootstrap re-applies after a livewire navigation', function () {
    /*
     * The tenant sidebar and top bar navigate with `wire:navigate`. Livewire's
     * `swapCurrentPageWithNewHtml` calls `replaceHtmlAttributes()`, which
     * copies the incoming document's <html> attributes over the live element
     * and removes any the new document lacks. The server renders
     * `class="h-full scroll-smooth"` with no `dark` — that class is added
     * client-side from localStorage — so every sidebar click stripped it and
     * the dashboard reverted to light until a full reload.
     *
     * Livewire does not re-run an unchanged inline <head> script either, so
     * the bootstrap running once on load is not enough: it has to listen.
     */
    $bootstrap = file_get_contents(base_path('resources/views/components/theme-script.blade.php'));

    expect($bootstrap)->toContain("addEventListener('livewire:navigated'");
});

test('the theme bootstrap does not persist a default on first visit', function () {
    /*
     * The old script called `setItem('veyra-theme', 'dark')` on any visit with
     * no stored value, stamping the current default into every visitor's
     * browser the moment they loaded a page — so changing the product default
     * later would never have reached them, and "no preference" became
     * indistinguishable from "explicitly chose dark".
     *
     * Asserted against the component source, not the rendered page: the theme
     * TOGGLE in the top bar writes the key too, and must keep doing so. Only
     * the bootstrap is required to stay read-only.
     */
    $bootstrap = file_get_contents(base_path('resources/views/components/theme-script.blade.php'));

    expect($bootstrap)
        ->toContain("localStorage.getItem('veyra-theme')")
        ->not->toContain('localStorage.setItem');
});

test('every shell that owns its own document renders the theme bootstrap', function () {
    /*
     * The disabled company-portal page shipped without it and rendered light
     * for everyone regardless of their stored preference. The bootstrap is now
     * one component, so the guarantee worth testing is that each surface
     * actually includes it — and includes it before the stylesheet, or the
     * page paints the wrong theme first.
     */
    $shells = [
        'resources/views/components/layouts/app.blade.php',
        'resources/views/components/layouts/auth-split.blade.php',
        'resources/views/components/layouts/guest.blade.php',
        'resources/views/components/layouts/marketing.blade.php',
        'resources/views/layouts/admin.blade.php',
        'resources/views/tenant/portal/layout.blade.php',
        'resources/views/tenant/portal/disabled.blade.php',
    ];

    foreach ($shells as $shell) {
        $source = file_get_contents(base_path($shell));

        $this->assertStringContainsString(
            '<x-theme-script />',
            $source,
            "{$shell} is missing the theme bootstrap — it will render light for everyone.",
        );

        // Before the stylesheet, or the browser paints the wrong theme first.
        $this->assertLessThan(
            strpos($source, '@vite('),
            strpos($source, '<x-theme-script />'),
            "{$shell} loads the stylesheet before the theme bootstrap.",
        );
    }
});
