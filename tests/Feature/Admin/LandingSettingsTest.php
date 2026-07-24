<?php

use App\Models\Setting;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

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
        ->and(session('flasher.message'))->toBe('Settings Updated successfully')
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
