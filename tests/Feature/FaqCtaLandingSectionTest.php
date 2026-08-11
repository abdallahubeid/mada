<?php

use App\Models\Faq;
use App\Models\Setting;
use Database\Seeders\FaqSeeder;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists faq and cta section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('faq_title'))->toBe('الأسئلة الشائعة')
        ->and(Setting::getValue('faq_sub_title'))->toBe('إجابات سريعة عن أكثر ما يسأل عنه عملاؤنا.')
        ->and(Setting::getValue('cta_title'))->toBe('ابدأ اليوم — مجاناً بالكامل')
        ->and(Setting::getValue('cta_sub_title'))->toBe('فعّل مؤسستك خلال دقائق: أنشئ حسابك، وادعُ فريقك، وابدأ التشغيل. دون رسوم ودون بطاقة ائتمان خلال الفترة الحالية.')
        ->and(Setting::getValue('cta_btn1_text'))->toBe('ابدأ مجاناً الآن')
        ->and(Setting::getValue('cta_btn1_link'))->toBe('/register')
        ->and(Setting::getValue('cta_btn2_text'))->toBe('تواصل مع المبيعات')
        ->and(Setting::getValue('cta_btn2_link'))->toBe('/contact');
});

test('faq seeder persists the six published landing faqs', function () {
    $this->seed(FaqSeeder::class);

    $faqs = Faq::query()->published()->get();

    // 7 since the 2026-08-09 content pass added the payroll-access security Q&A.
    expect($faqs)->toHaveCount(9)
        ->and($faqs->first()->question)->toBe('هل أحتاج إلى خبرة تقنية لاستخدام النظام؟')
        ->and($faqs->pluck('question')->all())->toContain('هل تتوفر تجربة مجانية؟');
});

test('the landing page faq and cta sections render seeded settings and content', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(FaqSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('الأسئلة الشائعة', false)
        ->assertSee('إجابات سريعة عن أكثر ما يسأل عنه عملاؤنا.', false)
        ->assertSee('هل أحتاج إلى خبرة تقنية لاستخدام النظام؟', false)
        // The isolation answer no longer claims per-tenant databases; it now
        // describes the row-level scoping the app actually implements.
        ->assertSee('يحمل كل سجل في النظام معرّف مؤسسته', false)
        ->assertSee('ابدأ اليوم — مجاناً بالكامل', false)
        ->assertSee('فعّل مؤسستك خلال دقائق: أنشئ حسابك، وادعُ فريقك، وابدأ التشغيل. دون رسوم ودون بطاقة ائتمان خلال الفترة الحالية.', false)
        ->assertSee('تواصل مع المبيعات', false)
        ->assertSee('/register', false)
        ->assertSee('/contact', false);
});
