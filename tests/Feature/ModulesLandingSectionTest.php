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
        ->and(Setting::getValue('modules_title'))->toBe('وحدات متكاملة لكل احتياجات مؤسستك')
        ->and(Setting::getValue('modules_sub_title'))->toBe('كل وحدة مصممة لتعمل بتناغم مع البقية، فتنساب البيانات بينها دون جهد.');
});

test('module seeder persists the six module cards', function () {
    $this->seed(ModuleSeeder::class);

    $modules = Module::query()->published()->get();

    expect($modules)->toHaveCount(6)
        ->and($modules->pluck('icon')->all())->toBe([
            'ph:users-three-bold',
            'ph:credit-card-bold',
            'ph:kanban-bold',
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
        ->assertSee('وحدات متكاملة لكل احتياجات مؤسستك', false)
        ->assertSee('كل وحدة مصممة لتعمل بتناغم مع البقية، فتنساب البيانات بينها دون جهد.', false)
        ->assertSee('الموارد البشرية', false)
        ->assertSee('المالية والرواتب', false)
        ->assertSee('المشاريع والعمليات', false)
        ->assertSee('الدعم والتذاكر', false)
        ->assertSee('إدارة المستأجرين', false)
        ->assertSee('الأمان والصلاحيات', false)
        ->assertSee('ph:users-three-bold', false)
        ->assertSee('ph:shield-check-bold', false);
});
