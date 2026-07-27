<?php

use App\Models\Offering;
use App\Models\Setting;
use Database\Seeders\OfferingSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists offerings section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('offerings_title'))->toBe('قوة تتناسب مع طموحاتك')
        ->and(Setting::getValue('offerings_sub_title'))->toBe('كل ما تحتاجه مؤسستك من أدوات إدارية وتشغيلية في نظام واحد متكامل.');
});

test('offering seeder persists the four offering cards', function () {
    $this->seed(OfferingSeeder::class);

    $offerings = Offering::query()->published()->get();

    expect($offerings)->toHaveCount(4)
        ->and($offerings->pluck('icon')->all())->toBe([
            'ph:shield-check-bold',
            'ph:users-three-bold',
            'ph:kanban-bold',
            'ph:credit-card-bold',
        ])
        ->and($offerings->first()->title)->toBe('أمان متعدد المستأجرين');
});

test('the landing page offerings section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(OfferingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('قوة تتناسب مع طموحاتك', false)
        ->assertSee('كل ما تحتاجه مؤسستك من أدوات إدارية وتشغيلية في نظام واحد متكامل.', false)
        ->assertSee('أمان متعدد المستأجرين', false)
        ->assertSee('التوظيف وإدارة الموارد البشرية', false)
        ->assertSee('المشاريع والعمليات', false)
        ->assertSee('الرواتب والتحليلات المالية', false)
        ->assertSee('ph:shield-check-bold', false)
        ->assertSee('ph:credit-card-bold', false);
});
