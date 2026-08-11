<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\Setting;
use App\Models\User;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('public navbar renders links in the locked marketing order', function () {
    $html = $this->get(route('landing'))
        ->assertOk()
        ->getContent();

    $positions = [
        'الصفحة الرئيسية' => mb_strpos($html, 'الصفحة الرئيسية'),
        'من نحن' => mb_strpos($html, 'من نحن'),
        'الوحدات' => mb_strpos($html, 'الوحدات'),
        'المميزات' => mb_strpos($html, 'المميزات'),
        'الأسعار' => mb_strpos($html, 'الأسعار'),
        'تواصل معنا' => mb_strpos($html, 'تواصل معنا'),
    ];

    expect(array_values($positions))->each->toBeInt()
        ->and($positions['الصفحة الرئيسية'])->toBeLessThan($positions['من نحن'])
        ->and($positions['من نحن'])->toBeLessThan($positions['الوحدات'])
        ->and($positions['الوحدات'])->toBeLessThan($positions['المميزات'])
        ->and($positions['المميزات'])->toBeLessThan($positions['الأسعار'])
        ->and($positions['الأسعار'])->toBeLessThan($positions['تواصل معنا']);

    expect($html)->toContain('href="/#modules"')
        ->and($html)->not->toContain('href="'.route('admin.dashboard').'"');
});

test('guests and tenant users never see the admin dashboard navbar link', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertDontSee('href="'.route('admin.dashboard').'"', false);

    $tenant = Tenant::factory()->create();
    $tenantUser = User::factory()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($tenantUser)
        ->get(route('landing'))
        ->assertOk()
        ->assertDontSee('href="'.route('admin.dashboard').'"', false);
});

test('platform admins see the dashboard link on the public navbar', function () {
    actingAsPlatformOperator();

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('href="'.route('admin.dashboard').'"', false)
        ->assertSee('لوحة التحكم', false);
});

// ---------------------------------------------------------------------------
// Auth-aware navbar CTA
// ---------------------------------------------------------------------------

/**
 * Just the <header> markup.
 *
 * These assertions must be scoped to the navbar: the landing page BODY carries
 * its own register CTAs (hero, bottom CTA, footer) which are a separate
 * question from the header's auth buttons. Asserting against the whole
 * document would fail on those and say nothing about the navbar.
 */
function navbarHtml(string $html): string
{
    $start = mb_strpos($html, '<header');
    $end = mb_strpos($html, '</header>');

    expect($start)->toBeInt()->and($end)->toBeInt();

    return mb_substr($html, $start, $end - $start);
}

test('guests see both auth buttons on the public navbar', function () {
    $nav = navbarHtml($this->get(route('landing'))->assertOk()->getContent());

    expect($nav)->toContain('href="'.route('login').'"')
        ->toContain('تسجيل الدخول')
        ->toContain('href="'.route('register').'"')
        ->toContain('ابدأ التجربة المجانية');
});

test('a signed-in tenant user sees a workspace CTA instead of the auth buttons', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $nav = navbarHtml($this->actingAs($user)->get(route('landing'))->assertOk()->getContent());

    /*
     * The defect: an authenticated visitor was being invited to sign in again
     * and to start a second free trial on an account they already have.
     */
    expect($nav)->not->toContain('href="'.route('login').'"')
        ->and($nav)->not->toContain('href="'.route('register').'"')
        ->and($nav)->not->toContain('ابدأ التجربة المجانية');

    expect($nav)->toContain('href="'.route('dashboard').'"')
        ->toContain('انتقل إلى لوحة التحكم');
});

test('a tenant still in onboarding is sent to setup, not to a dashboard it cannot open', function () {
    foreach ([TenantStatus::PendingVerification, TenantStatus::PendingApproval] as $status) {
        $tenant = Tenant::factory()->create(['status' => $status]);
        $user = User::factory()->create(['tenant_id' => $tenant->id]);

        /*
         * `dashboard` sits behind `tenant.active`, so pointing these users
         * there would hand them a 403 from a button that promised a dashboard.
         * The setup wizard is reachable because it is registered under
         * `tenant.context` only.
         */
        $nav = navbarHtml($this->actingAs($user)->get(route('landing'))->assertOk()->getContent());

        expect($nav)->toContain('href="'.route('dashboard.setup').'"')
            ->toContain('أكمل إعداد مؤسستك')
            ->and($nav)->not->toContain('href="'.route('login').'"');
    }
});

test('a suspended tenant is sent to the dashboard so the 403 can explain why', function () {
    $tenant = Tenant::factory()->suspended()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    // The per-status refusal message is the useful destination here; the setup
    // wizard would imply the account merely needs finishing.
    $nav = navbarHtml($this->actingAs($user)->get(route('landing'))->assertOk()->getContent());

    expect($nav)->toContain('href="'.route('dashboard').'"')
        ->and($nav)->not->toContain('href="'.route('dashboard.setup').'"');
});

test('the signed-in CTA replaces the auth buttons in the mobile menu too', function () {
    $tenant = Tenant::factory()->active()->create();
    $user = User::factory()->create(['tenant_id' => $tenant->id]);

    $nav = navbarHtml($this->actingAs($user)->get(route('landing'))->assertOk()->getContent());

    // The desktop bar and the mobile menu each render their own copy, so the
    // CTA must appear twice within the header — a fix applied to only one of
    // them still leaves "تسجيل الدخول" in front of a signed-in user.
    expect(mb_substr_count($nav, 'انتقل إلى لوحة التحكم'))->toBe(2)
        ->and($nav)->not->toContain('تسجيل الدخول');
});

test('an admin whose role cannot open the console dashboard still gets a reachable CTA', function () {
    // preferredAdminHomeRoute() picks the first console page the operator holds
    // permission for, so a hardcoded admin.dashboard link would be the wrong
    // destination for any role lacking dashboard.view.
    $operator = actingAsPlatformOperator(PlatformPermissionCatalog::ROLE_CONTENT_MANAGER);

    $nav = navbarHtml($this->get(route('landing'))->assertOk()->getContent());

    expect($nav)->toContain('href="'.route($operator->preferredAdminHomeRoute()).'"')
        ->and($nav)->not->toContain('href="'.route('login').'"');
});

test('admin sidebar uses site_logo when set and links to the public home route', function () {
    Storage::fake('custom');
    actingAsPlatformOperator();

    $logoPath = UploadedFile::fake()->create('logo.png', 10, 'image/png')->store('uploads/settings', 'custom');
    Setting::query()->updateOrCreate(['key' => 'site_logo'], ['value' => $logoPath]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(Setting::assetUrl($logoPath), false)
        ->assertSee('href="'.route('landing').'"', false)
        ->assertSee('aria-label="الصفحة الرئيسية — Veyra ERP"', false);
});

test('admin sidebar falls back to the default brand mark when site_logo is empty', function () {
    actingAsPlatformOperator();
    $this->seed(SettingSeeder::class);

    Setting::query()->where('key', 'site_logo')->update(['value' => null]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Veyra', false)
        ->assertSee('Platform Console', false)
        ->assertSee('href="'.route('landing').'"', false)
        ->assertDontSee('uploads/settings/', false);
});
