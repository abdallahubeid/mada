<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Models\Setting;
use App\Models\User;
use App\Services\Marketing\MarketingContent;
use Database\Seeders\DemoTenantSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;

uses(RefreshDatabase::class);

test('setting seeder persists product previews section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('product_previews_badge_text'))->toBe('جولة في المنتج')
        ->and(Setting::getValue('product_previews_title'))->toBe('شاهد النظام **قبل أن تسجّل**')
        ->and(Setting::getValue('product_previews_sub_title'))->toBe('واجهة عربية بالكامل، مبنية من اليمين لليسار — لا ترجمة مقلوبة ولا شاشات نصفها إنجليزي.');
});

test('product preview stats use database counts with fallbacks when empty', function () {
    Cache::flush();

    $stats = app(MarketingContent::class)->productPreviewStats();

    expect($stats['tenants']['value'])->toBe(1284)
        ->and($stats['employees']['value'])->toBe(18420)
        ->and($stats['revenue']['value'])->toBe(458)
        ->and($stats['revenue']['suffix'])->toBe('K')
        ->and($stats['uptime']['value'])->toBe(99.9);
});

test('product preview stats reflect live tenant and user counts', function () {
    Cache::flush();

    $this->seed(DemoTenantSeeder::class);
    User::factory()->count(3)->create();

    $stats = app(MarketingContent::class)->productPreviewStats();

    expect($stats['tenants']['value'])->toBe(Tenant::query()->count())
        ->and($stats['employees']['value'])->toBe(User::query()->count());
});

test('the landing page product previews section renders seeded settings and stats', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('جولة في المنتج', false)
        ->assertSee('شاهد النظام', false)->assertSee('قبل أن تسجّل', false)
        ->assertSee('واجهة عربية بالكامل، مبنية من اليمين لليسار — لا ترجمة مقلوبة ولا شاشات نصفها إنجليزي.', false)
        ->assertSee('المستأجرون', false)
        ->assertSee('الموظفون', false)
        ->assertSee('الإيرادات', false)
        ->assertSee('الجاهزية', false)
        ->assertSee('1,284', false)
        ->assertSee('18,420', false);
});
