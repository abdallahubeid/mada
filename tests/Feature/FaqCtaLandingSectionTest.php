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
        ->and(Setting::getValue('cta_title'))->toBe('جاهز لتحويل مؤسستك؟')
        ->and(Setting::getValue('cta_sub_title'))->toBe('ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق.')
        ->and(Setting::getValue('cta_btn1_text'))->toBe('ابدأ التجربة المجانية')
        ->and(Setting::getValue('cta_btn1_link'))->toBe('/register')
        ->and(Setting::getValue('cta_btn2_text'))->toBe('تواصل مع المبيعات')
        ->and(Setting::getValue('cta_btn2_link'))->toBe('/contact');
});

test('faq seeder persists the six published landing faqs', function () {
    $this->seed(FaqSeeder::class);

    $faqs = Faq::query()->published()->get();

    expect($faqs)->toHaveCount(6)
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
        ->assertSee('Multi-tenancy isolation', false)
        ->assertSee('جاهز لتحويل مؤسستك؟', false)
        ->assertSee('ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق.', false)
        ->assertSee('تواصل مع المبيعات', false)
        ->assertSee('/register', false)
        ->assertSee('/contact', false);
});
