<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('settings page exposes privacy terms and social fields', function () {
    $this->get(route('admin.landing.settings.edit'))
        ->assertOk()
        ->assertSee('privacy_title', false)
        ->assertSee('terms_title', false)
        ->assertSee('social_btn1_text', false)
        ->assertSee('Privacy', false)
        ->assertSee('Terms', false)
        ->assertSee('Social Media', false);
});

test('settings update persists privacy terms and social keys', function () {
    $this->put(route('admin.landing.settings.update'), [
        'privacy_title' => 'سياسة الخصوصية',
        'privacy_description' => 'نص الخصوصية',
        'terms_title' => 'الشروط والأحكام',
        'terms_btn_link' => '/terms',
        'social_btn1_text' => 'Twitter',
        'social_btn1_link' => 'https://x.com/mada',
        'social_btn2_text' => 'LinkedIn',
        'social_btn2_link' => 'https://linkedin.com/company/mada',
    ])->assertRedirect(route('admin.landing.settings.edit'));

    expect(Setting::getValue('privacy_title'))->toBe('سياسة الخصوصية')
        ->and(Setting::getValue('privacy_description'))->toBe('نص الخصوصية')
        ->and(Setting::getValue('terms_title'))->toBe('الشروط والأحكام')
        ->and(Setting::getValue('terms_btn_link'))->toBe('/terms')
        ->and(Setting::getValue('social_btn1_text'))->toBe('Twitter')
        ->and(Setting::getValue('social_btn1_link'))->toBe('https://x.com/mada')
        ->and(Setting::getValue('social_btn2_text'))->toBe('LinkedIn')
        ->and(session('flasher.type'))->toBe('info');
});

test('landing keys catalog includes privacy terms and social keys', function () {
    $keys = Setting::landingKeys();

    expect($keys)->toContain('privacy_badge_text')
        ->and($keys)->toContain('terms_description')
        ->and($keys)->toContain('social_btn5_link');
});
