<?php

use App\Models\Setting;
use Database\Seeders\SettingSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists hero section content', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('hero_badge_text'))->toBe('منصة SaaS متكاملة لإدارة المؤسسات')
        ->and(Setting::getValue('hero_title'))->toBe('مستقبل إدارة المؤسسات بذكاء وفخامة')
        ->and(Setting::getValue('hero_description'))->toBe('منصة Veyra ERP الشاملة لإدارة الموارد البشرية، المشاريع، والرواتب — أتمتة كاملة لعمليات مؤسستك في نظام واحد أنيق وذكي، بدقة تنظيمية وأمان تام لبياناتك.')
        ->and(Setting::getValue('hero_btn1_text'))->toBe('ابدأ التجربة المجانية')
        ->and(Setting::getValue('hero_btn1_link'))->toBe('#')
        ->and(Setting::getValue('hero_btn2_text'))->toBe('احجز عرضًا توضيحيًا')
        ->and(Setting::getValue('hero_btn2_link'))->toBe('#');
});

test('the landing page hero renders seeded settings', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('منصة SaaS متكاملة لإدارة المؤسسات', false)
        ->assertSee('مستقبل إدارة المؤسسات بذكاء وفخامة', false)
        ->assertSee('احجز عرضًا توضيحيًا', false)
        ->assertSee('ابدأ التجربة المجانية', false);
});
