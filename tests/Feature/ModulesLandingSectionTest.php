<?php

use App\Models\Module;
use App\Models\Setting;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists modules section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('modules_badge_text'))->toBe('الوحدات')
        ->and(Setting::getValue('modules_title'))->toBe('وحدات تعمل معاً، لا بجوار بعضها')
        ->and(Setting::getValue('modules_sub_title'))->toBe('كل وحدة تكتب وتقرأ من البيانات المعزولة نفسها، فلا يوجد رقمان لنفس الحقيقة ولا مطابقة يدوية في نهاية الشهر.');
});

test('module seeder persists the six module cards', function () {
    $this->seed(ModuleSeeder::class);

    $modules = Module::query()->published()->get();

    expect($modules)->toHaveCount(6)
        ->and($modules->pluck('icon')->all())->toBe([
            'ph:users-three-bold',
            'ph:credit-card-bold',
            'ph:identification-badge-bold',
            'ph:chat-teardrop-dots-bold',
            'ph:buildings-bold',
            'ph:shield-check-bold',
        ])
        ->and($modules->first()->title)->toBe('الموارد البشرية');
});

test('the landing page modules section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(ModuleSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('الوحدات', false)
        ->assertSee('وحدات تعمل معاً، لا بجوار بعضها', false)
        ->assertSee('كل وحدة تكتب وتقرأ من البيانات المعزولة نفسها، فلا يوجد رقمان لنفس الحقيقة ولا مطابقة يدوية في نهاية الشهر.', false)
        ->assertSee('الموارد البشرية', false)
        ->assertSee('المالية والرواتب', false)
        ->assertSee('التوظيف والمقابلات', false)
        ->assertSee('الدعم والتذاكر', false)
        ->assertSee('إدارة المستأجرين', false)
        ->assertSee('الأمان والصلاحيات', false)
        ->assertSee('ph:users-three-bold', false)
        ->assertSee('ph:shield-check-bold', false);
});
