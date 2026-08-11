<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Mail\Tenancy\TenantReactivatedMail;
use App\Mail\Tenancy\TenantRejectedMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/**
 * The shared transactional email shell (resources/views/vendor/mail/html/*).
 *
 * Rendered rather than asserted against the Blade source, so these cover what
 * a mail client actually receives — after the CSS inliner has run and the
 * theme has been folded into style attributes.
 *
 * @return array{0: Tenant, 1: User}
 */
function mailFixtures(): array
{
    $tenant = Tenant::factory()->create(['name' => 'شركة الأفق للتقنية']);
    $owner = User::factory()->create(['tenant_id' => $tenant->id]);

    return [$tenant, $owner];
}

test('no Laravel branding reaches a customer inbox', function () {
    [$tenant, $owner] = mailFixtures();

    $html = (new TenantReactivatedMail($tenant, $owner))->render();

    /*
     * Two separate defects fed this:
     *
     *  1. The published header carried an `@elseif (trim($slot) === 'Laravel')`
     *     branch that pulled a logo from laravel.com into the message.
     *  2. `APP_NAME` was still `Laravel`, so `config('app.name')` rendered it as
     *     the wordmark, the sign-off and the copyright holder — the footer read
     *     "© 2026 Laravel. جميع الحقوق محفوظة."
     *
     * The second is why this asserts on rendered output rather than on the
     * templates: the templates were already brand-neutral and still produced
     * Laravel branding.
     */
    expect($html)->not->toContain('Laravel')
        ->and($html)->not->toContain('laravel.com');
});

test('the mail shell carries Veyra identity and the legal footer', function () {
    [$tenant, $owner] = mailFixtures();

    $html = (new TenantReactivatedMail($tenant, $owner))->render();

    expect($html)->toContain('Veyra ERP')
        ->toContain('© '.date('Y').' Veyra ERP. جميع الحقوق محفوظة.')
        // Identity tagline and the support/site links.
        ->toContain('منصّة إدارة موارد المؤسسات')
        ->toContain(route('marketing.contact'))
        ->toContain(route('landing'));
});

test('messages render right-to-left', function () {
    [$tenant, $owner] = mailFixtures();

    $html = (new TenantReactivatedMail($tenant, $owner))->render();

    // Every message is Arabic; the app locale is `en`, so direction is pinned
    // in the layout rather than derived from it.
    expect($html)->toContain('dir="rtl"')
        ->toContain('lang="ar"');
});

test('the primary button uses the brand emerald with readable dark text', function () {
    [$tenant, $owner] = mailFixtures();

    $html = (new TenantReactivatedMail($tenant, $owner))->render();

    /*
     * Dark ink on emerald, not white. White on #4EDEA3 measures 1.7:1; ink
     * measures 10.8:1, and it matches the product's own button treatment.
     * Laravel's default primary was #18181b, an off-brand near-black.
     */
    expect($html)->toContain('4edea3')
        ->and($html)->not->toContain('#18181b');
});

test('the reason panel rule sits on the right for Arabic copy', function () {
    [$tenant, $owner] = mailFixtures();

    $reason = 'بيانات السجل التجاري غير مكتملة.';
    $html = (new TenantRejectedMail($tenant, $owner, $reason))->render();

    // A left rule would sit at the END of right-to-left text rather than at
    // its start, which is where an accent rule has to be to read as one.
    expect($html)->toContain($reason)
        ->toContain('border-right')
        ->and($html)->not->toContain('border-left: #18181b');
});
