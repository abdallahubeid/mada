<?php

use App\Models\Offering;
use App\Models\Setting;
use Database\Seeders\OfferingSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists offerings section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('offerings_title'))->toBe('أربع ركائز يقوم عليها النظام')
        ->and(Setting::getValue('offerings_sub_title'))->toBe('كل ركيزة مبنية ومُفعّلة اليوم — ما هو قيد التطوير معروض بوضوح في خارطة الطريق أدناه.');
});

test('offering seeder persists the four offering cards', function () {
    $this->seed(OfferingSeeder::class);

    $offerings = Offering::query()->published()->get();

    expect($offerings)->toHaveCount(4)
        ->and($offerings->pluck('icon')->all())->toBe([
            'ph:shield-check-bold',
            'ph:users-three-bold',
            'ph:check-square-offset-bold',
            'ph:credit-card-bold',
        ])
        ->and($offerings->first()->title)->toBe('أمان متعدد المستأجرين');
});

test('the landing page offerings section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(OfferingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('أربع ركائز يقوم عليها النظام', false)
        ->assertSee('كل ركيزة مبنية ومُفعّلة اليوم — ما هو قيد التطوير معروض بوضوح في خارطة الطريق أدناه.', false)
        ->assertSee('أمان متعدد المستأجرين', false)
        ->assertSee('التوظيف وإدارة الموارد البشرية', false)
        ->assertSee('محرّك الموافقات وسجل التدقيق', false)
        ->assertSee('الرواتب والمصروفات', false)
        ->assertSee('M9 12.75 11.25 15 15 9.75m-3-7.036', false)
        ->assertSee('M2.25 8.25h19.5M2.25 9h19.5', false);
});
