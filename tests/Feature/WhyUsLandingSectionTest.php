<?php

use App\Models\Feature;
use App\Models\Setting;
use Database\Seeders\FeatureSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists why us section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('why_us_badge_text'))->toBe('لماذا Veyra')
        ->and(Setting::getValue('why_us_title'))->toBe('ما الذي يميّزنا عن غيرنا')
        ->and(Setting::getValue('why_us_sub_title'))->toBe('لم نبنِ مجرد أداة أخرى، بل منصّة تفهم طبيعة المؤسسات في منطقتنا.');
});

test('feature seeder persists the four why us cards', function () {
    $this->seed(FeatureSeeder::class);

    $features = Feature::query()->published()->get();

    expect($features)->toHaveCount(4)
        ->and($features->pluck('icon')->all())->toBe([
            'ph:translate-bold',
            'ph:shield-check-bold',
            'ph:arrow-down-bold',
            'ph:chat-dots-bold',
        ])
        ->and($features->first()->title)->toBe('عربي أولاً');
});

test('the landing page why us section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(FeatureSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('لماذا Veyra', false)
        ->assertSee('ما الذي يميّزنا عن غيرنا', false)
        ->assertSee('لم نبنِ مجرد أداة أخرى، بل منصّة تفهم طبيعة المؤسسات في منطقتنا.', false)
        ->assertSee('عربي أولاً', false)
        ->assertSee('أمان بمعايير المؤسسات', false)
        ->assertSee('إعداد سريع', false)
        ->assertSee('دعم يتحدث لغتك', false)
        ->assertSee('ph:translate-bold', false)
        ->assertSee('ph:shield-check-bold', false);
});
