<?php

use Database\Seeders\OfferingSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

/**
 * Smoke coverage for the public marketing pages (docs/MARKETING.md §5.5).
 *
 * The two data-driven tests below assert CMS-seeded copy. They previously
 * seeded nothing and passed on leftover rows committed by an earlier run,
 * which made them order-dependent — the 2026-08-09 content pass surfaced that
 * by changing the copy. Each now seeds exactly what it asserts. The solutions
 * and security pages render static view content and need no seed.
 */
uses(RefreshDatabase::class);

test('the features page renders successfully', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(OfferingSeeder::class);

    $this->get(route('marketing.features'))
        ->assertOk()
        ->assertSee('المميزات')
        ->assertSee('أمان متعدد المستأجرين')
        ->assertSee('ابدأ مجاناً الآن');
});

test('the solutions page renders industry sections with anchors', function () {
    $this->get(route('marketing.solutions'))
        ->assertOk()
        ->assertSee('الحلول')
        ->assertSee('الجهات الحكومية')
        ->assertSee('id="government"', false)
        ->assertSee('المنظمات غير الربحية');
});

test('the pricing page renders plan tiers from the shared catalog', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(PlanSeeder::class);

    $this->get(route('marketing.pricing'))
        ->assertOk()
        ->assertSee('الأسعار')
        ->assertSee('الأساسية', false)
        ->assertSee('النمو', false)
        ->assertSee('Enterprise')
        ->assertSee('الأكثر طلباً');
});

test('the security page renders compliance pillars', function () {
    $this->get(route('marketing.security'))
        ->assertOk()
        ->assertSee('الأمان والامتثال')
        ->assertSee('عزل بيانات متعدد المستأجرين')
        ->assertSee('تحقق بخطوتين')
        ->assertSee('بانتظار التحقق');
});
