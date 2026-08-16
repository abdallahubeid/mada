<?php

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists hero section content', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('hero_badge_text'))->toBe('مجاني بالكامل خلال فترة الإطلاق')
        ->and(Setting::getValue('hero_title'))->toBe('كل ما تحتاجه لإدارة ((فريقك))، **في مكان واحد**')
        ->and(Setting::getValue('hero_description'))->toBe('التوظيف، العقود، الحضور، الإجازات، والرواتب — من نفس الشاشة. بدون جداول جانبية، وبدون أنظمة متفرقة لا يتحدث بعضها مع بعض.')
        ->and(Setting::getValue('hero_btn1_text'))->toBe('ابدأ الآن — مجاناً')
        // Both hero buttons pointed at '#'. They now go to real routes.
        ->and(Setting::getValue('hero_btn1_link'))->toBe('/register')
        ->and(Setting::getValue('hero_btn2_text'))->toBe('تواصل مع مستشار')
        ->and(Setting::getValue('hero_btn2_link'))->toBe('/contact')
        ->and(Setting::getValue('problems_badge_text'))->toBe('التحديات')
        ->and(Setting::getValue('problems_title'))->toBe('أين تتسرّب كفاءة مؤسستك اليوم؟')
        ->and(Setting::getValue('problems_sub_title'))->toBe('المشكلة نادراً ما تكون في الأدوات نفسها، بل في المسافة بينها — وهذه هي الفجوات التي تكلّف وقتاً ومالاً وثقة.');
});

test('the landing page hero renders seeded settings', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('مجاني بالكامل خلال فترة الإطلاق', false)
        /*
         * The title is asserted in two halves, not as the stored string. The
         * stored value carries `**` delimiters marking the phrase that gets the
         * hand-drawn highlight, and the hero splits on them — so the rendered
         * markup is `كل ما تحتاجه لإدارة فريقك، <span class="mada-marker">في
         * مكان واحد</span>` and never contains the raw value.
         */
        ->assertSee('كل ما تحتاجه لإدارة', false)->assertSee('فريقك', false)
        ->assertSee('في مكان واحد', false)
        ->assertSee('mada-marker', false)
        ->assertSee('تواصل مع مستشار', false)
        ->assertSee('ابدأ الآن — مجاناً', false);
});
