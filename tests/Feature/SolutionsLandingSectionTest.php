<?php

use App\Models\Module;
use App\Models\Setting;
use App\Models\Solution;
use Database\Seeders\ModuleSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\SolutionSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists solutions section chrome and cta', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('solutions_badge_text'))->toBe('الحل')
        ->and(Setting::getValue('solutions_title'))->toBe('مصدر واحد للحقيقة عبر مؤسستك بالكامل')
        ->and(Setting::getValue('solutions_sub_title'))->toBe('تنتقل البيانات من إعلان الوظيفة إلى العقد إلى الحضور إلى مسيّرة الرواتب دون إعادة إدخال واحدة، ودون جدول جانبي واحد.')
        ->and(Setting::getValue('solutions_btn_text'))->toBe('اكتشف كل المميزات')
        ->and(Setting::getValue('solutions_btn_link'))->toBe('#modules');
});

test('module seeder persists the six landing modules', function () {
    $this->seed(ModuleSeeder::class);

    $modules = Module::query()->published()->get();

    expect($modules)->toHaveCount(6)
        ->and($modules->first()->title)->toBe('الموارد البشرية');
});

test('solution seeder persists the four bullet points', function () {
    $this->seed(SolutionSeeder::class);

    $solutions = Solution::query()->published()->get();

    expect($solutions)->toHaveCount(4)
        ->and($solutions->pluck('icon')->unique()->all())->toBe(['ph:check-bold'])
        ->and($solutions->first()->title)->toBe('دورة حياة الموظف كاملة في مسار واحد: من إعلان الوظيفة إلى المقابلة إلى العقد إلى مستحق نهاية الخدمة.');
});

test('the landing page solutions section renders seeded settings and sidebar modules', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(SolutionSeeder::class);
    $this->seed(ModuleSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('الحل', false)
        ->assertSee('مصدر واحد للحقيقة عبر مؤسستك بالكامل', false)
        ->assertSee('تنتقل البيانات من إعلان الوظيفة إلى العقد إلى الحضور إلى مسيّرة الرواتب دون إعادة إدخال واحدة، ودون جدول جانبي واحد.', false)
        ->assertSee('دورة حياة الموظف كاملة في مسار واحد: من إعلان الوظيفة إلى المقابلة إلى العقد إلى مستحق نهاية الخدمة.', false)
        ->assertSee('محرّك موافقات موحّد للإجازات والمصروفات ومسيّرات الرواتب، بفصل صارم بين مَن يُعدّ المعاملة ومَن يعتمدها.', false)
        ->assertSee('ph:check-bold', false)
        ->assertSee('اكتشف كل المميزات', false)
        ->assertSee('#modules', false)
        ->assertSee('الموارد البشرية', false)
        ->assertSee('المالية والرواتب', false)
        ->assertSee('التوظيف والمقابلات', false)
        ->assertSee('الدعم والتذاكر', false);
});
