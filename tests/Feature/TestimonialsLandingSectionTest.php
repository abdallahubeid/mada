<?php

use App\Models\Setting;
use App\Models\Testimonial;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('setting seeder persists testimonials section chrome', function () {
    $this->seed(SettingSeeder::class);

    expect(Setting::getValue('testimonials_badge_text'))->toBe('قصص نجاح')
        ->and(Setting::getValue('testimonials_title'))->toBe('مؤسسات تنمو مع Veyra')
        ->and(Setting::getValue('testimonials_sub_title'))->toBe('لا تأخذ كلامنا فقط — استمع لمن اختبروا الفرق بأنفسهم.');
});

test('testimonial seeder persists the three success story cards', function () {
    $this->seed(TestimonialSeeder::class);

    $testimonials = Testimonial::query()->published()->get();

    expect($testimonials)->toHaveCount(3)
        ->and($testimonials->pluck('rate')->unique()->all())->toBe([5])
        ->and($testimonials->first()->client_name)->toBe('سارة المطيري')
        ->and($testimonials->first()->client_role)->toBe('مديرة الموارد البشرية');
});

test('the landing page testimonials section renders seeded settings and cards', function () {
    $this->seed(SettingSeeder::class);
    $this->seed(TestimonialSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('قصص نجاح', false)
        ->assertSee('مؤسسات تنمو مع Veyra', false)
        ->assertSee('لا تأخذ كلامنا فقط — استمع لمن اختبروا الفرق بأنفسهم.', false)
        ->assertSee('سارة المطيري', false)
        ->assertSee('عبدالله الشمري', false)
        ->assertSee('ريم الدوسري', false)
        ->assertSee('مديرة الموارد البشرية · مجموعة الأفق', false)
        ->assertSee('وحّدنا خمسة أنظمة متفرقة في منصّة واحدة', false);
});
