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
        ->and(Setting::getValue('ai_title'))->toBe('ما نعمل عليه الآن')
        ->and(Setting::getValue('ai_sub_title'))->toBe('قدرات قيد التطوير ولم تُطلَق بعد. نعرضها صراحةً كي تعرف بدقة ما هو متاح اليوم وما هو قادم — دون مفاجآت بعد الاشتراك.');
});

test('ai feature seeder persists the three roadmap cards', function () {
    $this->seed(AiFeatureSeeder::class);

    $features = AiFeature::query()->published()->get();

    expect($features)->toHaveCount(3)
        ->and($features->pluck('icon')->unique()->all())->toBe(['ph:sparkle-bold'])
        ->and($features->first()->title)->toBe('الدفتر المحاسبي والقوائم المالية');
});

test('the landing page ai section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(AiFeatureSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('قريباً · خارطة الطريق', false)
        ->assertSee('ما نعمل عليه الآن', false)
        ->assertSee('قدرات قيد التطوير ولم تُطلَق بعد. نعرضها صراحةً كي تعرف بدقة ما هو متاح اليوم وما هو قادم — دون مفاجآت بعد الاشتراك.', false)
        ->assertSee('مساعد ذكي للموارد البشرية', false)
        ->assertSee('رؤى مالية تنبؤية', false)
        // The accounting ledger is advertised ONLY here, under the roadmap
        // badge — never in the finance offering, where it would read as shipped.
        ->assertSee('الدفتر المحاسبي والقوائم المالية', false)
        ->assertSee('ph:sparkle-bold', false)
        ->assertSee('قريباً', false);
});
