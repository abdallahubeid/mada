<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('settings page renders landing cms form', function () {
    Setting::query()->create([
        'key' => 'hero_title',
        'value' => 'عنوان تجريبي',
    ]);

    $this->get(route('admin.landing.settings.edit'))
        ->assertOk()
        ->assertSee('إعدادات صفحة الهبوط', false)
        ->assertSee('hero_title', false)
        ->assertSee('عنوان تجريبي', false)
        ->assertSee('footer_title1', false);
});

test('settings update persists key value pairs and file uploads', function () {
    Storage::fake('custom');

    $this->put(route('admin.landing.settings.update'), [
        'hero_title' => 'مستقبل المؤسسات',
        'hero_badge_text' => 'منصة SaaS',
        'cta_title' => 'ابدأ الآن',
        'site_logo' => UploadedFile::fake()->create('logo.png', 100, 'image/png'),
        '_token' => 'ignored',
        '_method' => 'PUT',
    ])->assertRedirect();

    expect(Setting::getValue('hero_title'))->toBe('مستقبل المؤسسات')
        ->and(Setting::getValue('hero_badge_text'))->toBe('منصة SaaS')
        ->and(Setting::getValue('cta_title'))->toBe('ابدأ الآن')
        ->and(Setting::getValue('site_logo'))->not->toBeNull()
        ->and(session('flasher.message'))->toBe('تم تحديث الإعدادات بنجاح.')
        ->and(session('flasher.type'))->toBe('info');

    Storage::disk('custom')->assertExists(Setting::getValue('site_logo'));
});

test('settings update keeps existing file when no new upload is provided', function () {
    Setting::query()->create([
        'key' => 'site_logo',
        'value' => 'uploads/settings/existing.png',
    ]);

    $this->put(route('admin.landing.settings.update'), [
        'hero_title' => 'عنوان جديد',
    ])->assertRedirect();

    expect(Setting::getValue('site_logo'))->toBe('uploads/settings/existing.png')
        ->and(Setting::getValue('hero_title'))->toBe('عنوان جديد');
});

test('settings image delete removes file and nulls branding setting', function () {
    Storage::fake('custom');

    $path = 'uploads/settings/logo.png';
    Storage::disk('custom')->put($path, 'logo-content');

    Setting::query()->create([
        'key' => 'site_logo',
        'value' => $path,
    ]);

    $this->deleteJson(route('admin.landing.settings.image.destroy', ['key' => 'site_logo']))
        ->assertOk()
        ->assertJson(['success' => true]);

    expect(Setting::getValue('site_logo'))->toBeNull();

    Storage::disk('custom')->assertMissing($path);
});

test('settings image delete rejects invalid keys', function () {
    $this->deleteJson(route('admin.landing.settings.image.destroy', ['key' => 'hero_title']))
        ->assertUnprocessable();
});

/*
 * ─────────────────────────────────────────────────────────────────────────────
 * DECORATION MARKERS MUST STAY EDITABLE
 *
 * Landing headlines encode their hand-drawn decorations in the string itself
 * (`**phrase**`, `((phrase))`, `__phrase__`), parsed at render time by
 * `x-marketing.hero` and `x-marketing.section-heading`.
 *
 * While the admin screen showed these as bare <input>s full of literal
 * asterisks, editing a title meant either retyping it and silently losing the
 * highlight, or not touching it. Nothing failed; the page just quietly went
 * plain. These tests exist because that is invisible in review — no error, no
 * broken layout, just a decoration that stopped appearing.
 * ─────────────────────────────────────────────────────────────────────────────
 */

/**
 * Every settings key whose value is parsed for decoration markers.
 *
 * `ai_title` and `cta_title` are deliberately ABSENT: `x-marketing.ai-capabilities`
 * and `x-marketing.cta-band` echo them with `{{ }}` and never parse. Giving them
 * a marker toolbar would advertise a decoration that renders as literal
 * asterisks on the public page.
 *
 * @return array<string, string> key => context
 */
function markerParsedSettingKeys(): array
{
    return [
        'hero_title' => 'hero',
        'problems_title' => 'section',
        'solutions_title' => 'section',
        'offerings_title' => 'section',
        'modules_title' => 'section',
        'product_previews_title' => 'section',
        'why_us_title' => 'section',
        'testimonials_title' => 'section',
        'pricing_title' => 'section',
        'faq_title' => 'section',
    ];
}

test('every marker-parsed title is edited through the decorated field', function () {
    $response = $this->get(route('admin.landing.settings.edit'))->assertOk();
    $html = $response->getContent();

    foreach (markerParsedSettingKeys() as $key => $context) {
        expect($html)
            ->toContain('data-decorated-key="' . $key . '"')
            ->and($html)->toContain('data-decorated-context="' . $context . '"');

        /*
         * The reverse assertion is the one that catches a regression: a future
         * edit that reverts the partial back to a plain field would still
         * satisfy "the key appears on the page".
         */
        expect($html)->not->toContain('<input type="text" name="' . $key . '"');
    }
});

test('the hero field offers all three markers and section headings offer only emphasis', function () {
    $html = $this->get(route('admin.landing.settings.edit'))->assertOk()->getContent();

    // The three decoration classes are the real ones from app.css — the preview
    // has to paint the same mark the public page will.
    expect($html)
        ->toContain('mada-marker')
        ->toContain('mada-circle')
        ->toContain('mada-underline-double');

    /*
     * `**` means the orange marker in the hero and the blue double underline in
     * a section heading. Exactly one field may declare the hero context; if a
     * second one does, the loud device has been applied twice.
     */
    expect(substr_count($html, 'data-decorated-context="hero"'))->toBe(1);
});

test('an existing marker value round-trips into the editor unchanged', function () {
    Setting::query()->create([
        'key' => 'hero_title',
        'value' => 'كل ما تحتاجه لإدارة ((فريقك))، **في مكان واحد**',
    ]);

    $html = $this->get(route('admin.landing.settings.edit'))->assertOk()->getContent();

    /*
     * The value reaches Alpine through `@js()`, i.e. `Illuminate\Support\Js`,
     * which encodes with JSON_UNESCAPED_UNICODE — so the Arabic stays literal
     * rather than becoming \uXXXX — and re-quotes the result in single quotes.
     *
     * Asserting the `value: '…'` fragment rather than the bare string is what
     * makes this test mean something: the title also appears elsewhere on the
     * page, so a plain `toContain` would pass even if the value never reached
     * the component.
     */
    expect($html)->toContain("value: 'كل ما تحتاجه لإدارة ((فريقك))، **في مكان واحد**',");
});
