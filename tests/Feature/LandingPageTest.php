<?php

use Database\Seeders\PlanSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * These two tests previously seeded nothing and asserted seeded CMS copy,
 * so they passed only on whatever rows happened to be committed in the test
 * database from an earlier run. That made them order-dependent: the 2026-08-09
 * content pass changed the copy and they began failing for a reason unrelated
 * to the page. They now seed exactly what they assert, like every other
 * landing-section test.
 */

test('the landing page renders with hero and pricing content', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(PlanSeeder::class);

    $this->get('/')
        ->assertOk()
        ->assertSee('Veyra')
        ->assertSee('ابدأ مجاناً الآن')
        ->assertSee('النمو', false)
        ->assertSee('الأكثر طلباً');
});

test('the landing page CTAs point to the expected destinations', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(PlanSeeder::class);

    $this->get('/')
        ->assertSee(route('login'), false)
        ->assertSee('/register', false);
});
