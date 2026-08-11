<?php

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists hero section content', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('hero_badge_text'))->toBe('منصّة ERP مؤسسية متعددة المستأجرين')
        ->and(Setting::getValue('hero_title'))->toBe('من أول إعلان وظيفة إلى آخر مستحق نهاية خدمة — في نظام واحد')
        ->and(Setting::getValue('hero_description'))->toBe('يدير Veyra دورة حياة الموظف كاملة: التوظيف والمقابلات، العقود والأقسام، الحضور والإجازات، مسيّرات رواتب باعتماد مزدوج تُقفل نهائياً بعد الاعتماد، المصروفات، وتسويات نهاية خدمة بقواعد تضبطها كل مؤسسة بنفسها. بيانات كل عميل معزولة على مستوى الصف، وكل عملية حسّاسة تترك أثراً في سجل التدقيق. مفتوح بجميع مزاياه ومجاناً خلال الفترة الحالية.')
        ->and(Setting::getValue('hero_btn1_text'))->toBe('ابدأ مجاناً الآن')
        // Both hero buttons pointed at '#'. They now go to real routes.
        ->and(Setting::getValue('hero_btn1_link'))->toBe('/register')
        ->and(Setting::getValue('hero_btn2_text'))->toBe('تصفّح الوحدات')
        ->and(Setting::getValue('hero_btn2_link'))->toBe('#modules')
        ->and(Setting::getValue('problems_badge_text'))->toBe('التحديات')
        ->and(Setting::getValue('problems_title'))->toBe('أين تتسرّب كفاءة مؤسستك اليوم؟')
        ->and(Setting::getValue('problems_sub_title'))->toBe('المشكلة نادراً ما تكون في الأدوات نفسها، بل في المسافة بينها — وهذه هي الفجوات التي تكلّف وقتاً ومالاً وثقة.');
});

test('the landing page hero renders seeded settings', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('منصّة ERP مؤسسية متعددة المستأجرين', false)
        ->assertSee('من أول إعلان وظيفة إلى آخر مستحق نهاية خدمة — في نظام واحد', false)
        ->assertSee('تصفّح الوحدات', false)
        ->assertSee('ابدأ مجاناً الآن', false);
});
