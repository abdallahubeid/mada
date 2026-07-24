<?php

use App\Models\Faq;
use App\Models\Plan;
use App\Models\Testimonial;
use App\Services\Marketing\MarketingContent;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->seed([
        \Database\Seeders\PlanSeeder::class,
        \Database\Seeders\FaqSeeder::class,
        \Database\Seeders\TestimonialSeeder::class,
        \Database\Seeders\SettingSeeder::class,
    ]);
});

test('marketing content reads plans faqs and testimonials from the database', function () {
    expect(Plan::query()->count())->toBe(3)
        ->and(Faq::query()->published()->count())->toBeGreaterThan(0)
        ->and(Testimonial::query()->published()->count())->toBe(3)
        ->and(config('marketing.hero.title_line_1'))->toBe('مستقبل إدارة');

    $content = app(MarketingContent::class)->home();

    expect($content['plans'])->toHaveCount(3)
        ->and($content['plans'][0]['name'])->toBe('Startup')
        ->and($content['faqs'])->not->toBeEmpty()
        ->and($content['testimonials'])->toHaveCount(3)
        ->and($content['hero']['resolved_metrics'])->toHaveCount(3)
        ->and($content['hero']['title_line_1'])->toBe('مستقبل إدارة')
        ->and($content['footer']['columns'])->not->toBeEmpty();
});

test('the landing page renders seeded marketing content', function () {
    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('Growth')
        ->assertSee('ما هو نظام Veyra ERP؟', false)
        ->assertSee('سارة المطيري');
});

test('the pricing page renders database plans', function () {
    $this->get(route('marketing.pricing'))
        ->assertOk()
        ->assertSee('Startup')
        ->assertSee('Enterprise');
});

test('the faq page renders published database faqs', function () {
    $this->get(route('marketing.faq'))
        ->assertOk()
        ->assertSee('هل تتوفر تجربة مجانية؟');
});
