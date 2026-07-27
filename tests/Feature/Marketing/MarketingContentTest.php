<?php

use App\Models\Faq;
use App\Models\Plan;
use App\Models\Testimonial;
use App\Services\Marketing\MarketingContent;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\SettingSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        PlanSeeder::class,
        FaqSeeder::class,
        TestimonialSeeder::class,
        SettingSeeder::class,
    ]);
});

test('marketing content reads plans faqs and testimonials from the database', function () {
    expect(Plan::query()->count())->toBe(3)
        ->and(Faq::query()->published()->count())->toBeGreaterThan(0)
        ->and(Testimonial::query()->published()->count())->toBe(3)
        ->and(config('marketing.hero.title_line_1'))->toBe('مستقبل إدارة');

    $content = app(MarketingContent::class)->home();

    expect($content['plans'])->toHaveCount(3)
        ->and($content['plans'][0]['name'])->toBe('الأساسية')
        ->and($content['faqs'])->toHaveCount(6)
        ->and($content['testimonials'])->toHaveCount(3)
        ->and($content['hero']['resolved_metrics'])->toHaveCount(3)
        ->and($content['hero']['title_line_1'])->toBe('مستقبل إدارة')
        ->and($content['footer']['columns'])->not->toBeEmpty();
});

test('the landing page renders seeded marketing content', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('النمو', false)
        ->assertSee('هل أحتاج إلى خبرة تقنية لاستخدام النظام؟', false)
        ->assertSee('سارة المطيري');
});

test('the pricing page renders database plans', function () {
    $this->get(route('marketing.pricing'))
        ->assertOk()
        ->assertSee('الأساسية', false)
        ->assertSee('Enterprise');
});

test('the faq page renders published database faqs', function () {
    $this->get(route('marketing.faq'))
        ->assertOk()
        ->assertSee('هل تتوفر تجربة مجانية؟');
});
