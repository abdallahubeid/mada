<?php

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
    $admin = User::factory()->create(['tenant_id' => null]);

    $this->actingAs($admin)
        ->get(route('landing'))
        ->assertOk()
        ->assertSee('href="'.route('admin.dashboard').'"', false)
        ->assertSee('لوحة التحكم', false);
});

test('admin sidebar uses site_logo when set and links to the public home route', function () {
    Storage::fake('custom');

    $logoPath = UploadedFile::fake()->create('logo.png', 10, 'image/png')->store('uploads/settings', 'custom');
    Setting::query()->updateOrCreate(['key' => 'site_logo'], ['value' => $logoPath]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee(Setting::assetUrl($logoPath), false)
        ->assertSee('href="'.route('landing').'"', false)
        ->assertSee('aria-label="الصفحة الرئيسية — Veyra ERP"', false);
});

test('admin sidebar falls back to the default brand mark when site_logo is empty', function () {
    $this->seed(SettingSeeder::class);

    Setting::query()->where('key', 'site_logo')->update(['value' => null]);

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertSee('Veyra', false)
        ->assertSee('Platform Console', false)
        ->assertSee('href="'.route('landing').'"', false)
        ->assertDontSee('uploads/settings/', false);
});
