<?php

use App\Models\AiFeature;
use App\Models\Setting;
use Database\Seeders\AiFeatureSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists ai section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('ai_badge_text'))->toBe('قريباً · خارطة الطريق')
        ->and(Setting::getValue('ai_title'))->toBe('ذكاء اصطناعي يعمل لصالحك')
        ->and(Setting::getValue('ai_sub_title'))->toBe('قدرات ذكية قيد التطوير ضمن خارطة طريق Veyra — نشاركك رؤيتنا القادمة بشفافية.');
});

test('ai feature seeder persists the three roadmap cards', function () {
    $this->seed(AiFeatureSeeder::class);

    $features = AiFeature::query()->published()->get();

    expect($features)->toHaveCount(3)
        ->and($features->pluck('icon')->unique()->all())->toBe(['ph:sparkle-bold'])
        ->and($features->first()->title)->toBe('مساعد ذكي للموارد البشرية');
});

test('the landing page ai section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(AiFeatureSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('قريباً · خارطة الطريق', false)
        ->assertSee('ذكاء اصطناعي يعمل لصالحك', false)
        ->assertSee('قدرات ذكية قيد التطوير ضمن خارطة طريق Veyra — نشاركك رؤيتنا القادمة بشفافية.', false)
        ->assertSee('مساعد ذكي للموارد البشرية', false)
        ->assertSee('رؤى مالية تنبؤية', false)
        ->assertSee('أتمتة سير العمل', false)
        ->assertSee('ph:sparkle-bold', false)
        ->assertSee('قريباً', false);
});
