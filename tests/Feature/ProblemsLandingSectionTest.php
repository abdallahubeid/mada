<?php

use App\Models\Problem;
use App\Models\Setting;
use Database\Seeders\ProblemSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists problems section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('problems_badge_text'))->toBe('التحديات')
        ->and(Setting::getValue('problems_title'))->toBe('أين تتسرّب كفاءة مؤسستك اليوم؟')
        ->and(Setting::getValue('problems_sub_title'))->toBe('المشكلة نادراً ما تكون في الأدوات نفسها، بل في المسافة بينها — وهذه هي الفجوات التي تكلّف وقتاً ومالاً وثقة.');
});

test('problem seeder persists the four challenge cards', function () {
    $this->seed(ProblemSeeder::class);

    $problems = Problem::query()->published()->get();

    expect($problems)->toHaveCount(4)
        ->and($problems->pluck('icon_key')->all())->toBe([
            'ph:link-bold',
            'ph:clock-bold',
            'ph:chart-bar-bold',
            'ph:warning-bold',
        ])
        ->and($problems->first()->title)->toBe('أنظمة متفرقة لا تتحدث معًا');
});

test('the landing page problems section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(ProblemSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('التحديات', false)
        ->assertSee('أين تتسرّب كفاءة مؤسستك اليوم؟', false)
        ->assertSee('المشكلة نادراً ما تكون في الأدوات نفسها، بل في المسافة بينها — وهذه هي الفجوات التي تكلّف وقتاً ومالاً وثقة.', false)
        ->assertSee('أنظمة متفرقة لا تتحدث معًا', false)
        ->assertSee('عمليات يدوية تستنزف الفرق', false)
        ->assertSee('غياب الرؤية المالية اللحظية', false)
        ->assertSee('مخاوف أمنية على البيانات', false)
        ->assertSee('ph:link-bold', false)
        ->assertSee('ph:warning-bold', false);
});
