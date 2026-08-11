<?php

use App\Models\Setting;
use App\Services\Marketing\MarketingContent;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('setting seeder persists footer and social content', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('footer_description'))->toBe('نظام تخطيط موارد مؤسسي متعدد المستأجرين: توظيف، موارد بشرية، حضور وإجازات، رواتب ومصروفات — بعزل بيانات وسجل تدقيق لكل مؤسسة.')
        ->and(Setting::getValue('footer_newsletter_title'))->toBe('البريد الإلكتروني')
        ->and(Setting::getValue('footer_newsletter_btn_text'))->toBe('اشتراك')
        ->and(Setting::getValue('footer_title1'))->toBe('المنتج')
        ->and(Setting::getValue('footer_btn1_text'))->toBe('المميزات')
        ->and(Setting::getValue('footer_btn1_link'))->toBe('/features')
        ->and(Setting::getValue('footer_title2'))->toBe('الشركة')
        ->and(Setting::getValue('footer_title3'))->toBe('القانونية')
        ->and(Setting::getValue('social_btn1_text'))->toBe('X / Twitter')
        ->and(Setting::getValue('social_btn1_link'))->toBe('https://twitter.com')
        ->and(Setting::getValue('social_btn5_link'))->toBe('https://youtube.com');
});

test('marketing content footer reads settings keys', function () {
    $this->seed(SettingSeeder::class);

    $footer = app(MarketingContent::class)->footer();

    expect($footer['blurb'])->toBe('نظام تخطيط موارد مؤسسي متعدد المستأجرين: توظيف، موارد بشرية، حضور وإجازات، رواتب ومصروفات — بعزل بيانات وسجل تدقيق لكل مؤسسة.')
        ->and($footer['newsletter_title'])->toBe('البريد الإلكتروني')
        ->and($footer['newsletter_btn_text'])->toBe('اشتراك')
        ->and($footer['columns'])->toHaveCount(3)
        ->and($footer['columns'][0]['title'])->toBe('المنتج')
        ->and($footer['columns'][0]['links'][0]['label'])->toBe('المميزات')
        ->and($footer['social'])->toHaveCount(5);
});

test('landing page footer renders seeded cms content', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('نظام تخطيط موارد مؤسسي متعدد المستأجرين: توظيف، موارد بشرية، حضور وإجازات، رواتب ومصروفات — بعزل بيانات وسجل تدقيق لكل مؤسسة.', false)
        ->assertSee('المنتج', false)
        ->assertSee('المميزات', false)
        ->assertSee('الشركة', false)
        ->assertSee('القانونية', false)
        ->assertSee('https://twitter.com', false)
        ->assertSee('https://youtube.com', false);
});

test('pricing page footer renders seeded cms content without footer prop', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('marketing.pricing'))
        ->assertOk()
        ->assertSee('المميزات', false)
        ->assertSee('https://linkedin.com', false);
});

test('admin settings footer tab shows footer and newsletter fields', function () {
    $this->get(route('admin.landing.settings.edit'))
        ->assertOk()
        ->assertSee('footer_description', false)
        ->assertSee('footer_newsletter_title', false)
        ->assertSee('footer_newsletter_btn_text', false)
        ->assertSee('Footer & Social', false);
});

test('landing keys include footer newsletter keys', function () {
    $keys = Setting::landingKeys();

    expect($keys)->toContain('footer_description')
        ->and($keys)->toContain('footer_newsletter_title')
        ->and($keys)->toContain('footer_newsletter_btn_text')
        ->and($keys)->toContain('footer_btn9_link')
        ->and($keys)->toContain('social_btn5_link');
});
