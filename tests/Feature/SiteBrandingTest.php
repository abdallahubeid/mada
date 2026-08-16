<?php

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('settings update persists site favicon upload', function () {
    Storage::fake('custom');

    $this->put(route('admin.landing.settings.update'), [
        'site_favicon' => UploadedFile::fake()->create('favicon.png', 10, 'image/png'),
    ])->assertRedirect();

    expect(Setting::getValue('site_favicon'))->not->toBeNull();

    Storage::disk('custom')->assertExists(Setting::getValue('site_favicon'));
});

test('the landing page renders uploaded logo and favicon from settings', function () {
    Storage::fake('custom');

    $logoPath = UploadedFile::fake()->create('logo.png', 10, 'image/png')->store('uploads/settings', 'custom');
    $faviconPath = UploadedFile::fake()->create('favicon.png', 10, 'image/png')->store('uploads/settings', 'custom');

    Setting::query()->updateOrCreate(['key' => 'site_logo'], ['value' => $logoPath]);
    Setting::query()->updateOrCreate(['key' => 'site_favicon'], ['value' => $faviconPath]);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee(Setting::assetUrl($logoPath), false)
        ->assertSee(Setting::assetUrl($faviconPath), false);
});

test('the landing page falls back to default branding assets when settings are empty', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('مدى', false)
        ->assertSee(asset('favicon.svg'), false);
});

test('the admin layout renders the dynamic favicon from settings', function () {
    Storage::fake('custom');

    $faviconPath = UploadedFile::fake()->create('favicon.png', 10, 'image/png')->store('uploads/settings', 'custom');
    Setting::query()->updateOrCreate(['key' => 'site_favicon'], ['value' => $faviconPath]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(Setting::assetUrl($faviconPath), false);
});

test('the admin layout falls back to default favicon when settings are empty', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(asset('favicon.svg'), false);
});
