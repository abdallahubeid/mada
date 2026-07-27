<?php

use App\Models\Plan;
use App\Models\Setting;
use Database\Seeders\PlanSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists pricing section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('pricing_title'))->toBe('استثمار ذكي لنمو مستدام')
        ->and(Setting::getValue('pricing_sub_title'))->toBe('اختر الخطة التي تناسب حجم مؤسستك، وطوّرها متى شئت.')
        ->and(Setting::getValue('pricing_btn_text'))->toBe('قارن جميع المزايا بالتفصيل')
        ->and(Setting::getValue('pricing_btn_link'))->toBe('/pricing');
});

test('plan seeder persists the three erp pricing tiers', function () {
    $this->seed(PlanSeeder::class);

    $plans = Plan::query()->active()->with('features')->get();

    expect($plans)->toHaveCount(3)
        ->and($plans->firstWhere('slug', 'startup')?->name)->toBe('الأساسية')
        ->and($plans->firstWhere('slug', 'growth')?->is_highlighted)->toBeTrue()
        ->and($plans->firstWhere('slug', 'growth')?->price_yearly)->toBe('99.00')
        ->and($plans->firstWhere('slug', 'enterprise')?->price_monthly)->toBeNull()
        ->and($plans->firstWhere('slug', 'startup')?->features)->toHaveCount(4);
});

test('the landing page pricing section renders seeded settings plans and comparison button', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(PlanSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('استثمار ذكي لنمو مستدام', false)
        ->assertSee('اختر الخطة التي تناسب حجم مؤسستك، وطوّرها متى شئت.', false)
        ->assertSee('الأساسية', false)
        ->assertSee('النمو', false)
        ->assertSee('Enterprise', false)
        ->assertSee('الأكثر طلباً', false)
        ->assertSee('قارن جميع المزايا بالتفصيل', false)
        ->assertSee('/pricing', false)
        ->assertSee('حتى 10 مستخدمين', false)
        ->assertSee('تواصل مع المبيعات', false);
});
