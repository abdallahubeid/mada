<?php

use App\Models\Plan;
use App\Models\Setting;
use Database\Seeders\PlanSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists pricing section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('pricing_title'))->toBe('مجاني بالكامل خلال الفترة الحالية')
        ->and(Setting::getValue('pricing_sub_title'))->toBe('كل الخطط مفتوحة بجميع مزاياها دون رسوم ودون بطاقة ائتمان. اختر الحجم الذي يناسب مؤسستك اليوم، وابدأ خلال دقائق.')
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
        /*
         * 7 = 3 plan limits + 4 marketing bullets. Both live in the same
         * `features` relation: PlanSeeder writes each `limits` entry as a
         * feature row keyed by `feature_key`, then each display bullet. The
         * expectation read 4 from when only the bullets were seeded.
         */
        ->and($plans->firstWhere('slug', 'startup')?->features)->toHaveCount(7);
});

test('the landing page pricing section renders seeded settings plans and comparison button', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(PlanSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('مجاني بالكامل خلال الفترة الحالية', false)
        ->assertSee('كل الخطط مفتوحة بجميع مزاياها دون رسوم ودون بطاقة ائتمان. اختر الحجم الذي يناسب مؤسستك اليوم، وابدأ خلال دقائق.', false)
        ->assertSee('الأساسية', false)
        ->assertSee('النمو', false)
        ->assertSee('Enterprise', false)
        ->assertSee('الأكثر طلباً', false)
        ->assertSee('قارن جميع المزايا بالتفصيل', false)
        ->assertSee('/pricing', false)
        // The free-status bullet leads every tier, so it is the one that must
        // reach the page — it is the whole point of the current pricing pass.
        ->assertSee('مجاني بالكامل خلال الفترة الحالية — بجميع المزايا ودون رسوم', false)
        ->assertSee('تواصل مع المبيعات', false);
});
