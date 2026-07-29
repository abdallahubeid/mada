<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Models\Faq;
use App\Models\Plan;
use App\Models\Testimonial;
use App\Services\Marketing\MarketingCache;
use App\Services\Marketing\MarketingContent;
use Database\Seeders\FaqSeeder;
use Database\Seeders\PlanSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

beforeEach(function () {
    $this->seed([
        PlanSeeder::class,
        FaqSeeder::class,
        TestimonialSeeder::class,
    ]);
});

test('plan update syncs features and invalidates marketing page cache', function () {
    $plan = Plan::query()->where('slug', 'growth')->firstOrFail();
    Cache::put(MarketingCache::PAGE_HOME, ['stale' => true], 600);

    $this->put(route('admin.plans.update', $plan), [
        'name' => 'Growth Plus',
        'slug' => 'growth',
        'tagline' => 'للشركات النامية',
        'price_monthly' => 349,
        'price_yearly' => 3490,
        'currency' => 'USD',
        'cta_label' => 'اشترك',
        'cta_url' => '/register',
        'is_highlighted' => true,
        'is_active' => true,
        'sort_order' => 2,
        'features_text' => "ميزة أ\nميزة ب",
    ])->assertRedirect(route('admin.plans'));

    $plan->refresh();

    expect($plan->name)->toBe('Growth Plus')
        ->and($plan->features()->pluck('label')->all())->toBe(['ميزة أ', 'ميزة ب'])
        ->and(Cache::has(MarketingCache::PAGE_HOME))->toBeFalse();
});

test('faq crud publishes and appears on public faq page', function () {
    $this->post(route('admin.faqs.store'), [
        'category' => 'اختبار',
        'question' => 'هل يعمل الحفظ؟',
        'answer' => 'نعم يعمل الحفظ من لوحة الإدارة.',
        'sort_order' => 1,
        'is_published' => true,
    ])->assertRedirect(route('admin.faqs.index'));

    $faq = Faq::query()->where('question', 'هل يعمل الحفظ؟')->first();
    expect($faq)->not->toBeNull();

    $this->get(route('marketing.faq'))
        ->assertOk()
        ->assertSee('هل يعمل الحفظ؟');

    $this->put(route('admin.faqs.update', $faq), [
        'category' => 'اختبار',
        'question' => 'هل يعمل التحديث؟',
        'answer' => 'نعم.',
        'sort_order' => 1,
        'is_published' => false,
    ])->assertRedirect(route('admin.faqs.index'));

    $this->get(route('marketing.faq'))
        ->assertOk()
        ->assertDontSee('هل يعمل التحديث؟');
});

test('testimonial store accepts avatar upload to custom disk', function () {
    Storage::fake('custom');

    $file = UploadedFile::fake()->create('avatar.png', 100, 'image/png');

    $this->post(route('admin.testimonials.store'), [
        'quote' => 'تجربة رائعة مع فيرا',
        'client_name' => 'أحمد',
        'client_role' => 'مدير',
        'organization_name' => 'مؤسسة الاختبار',
        'rate' => 5,
        'sort_order' => 10,
        'is_published' => true,
        'avatar' => $file,
        'alt_text' => 'صورة الاختبار',
    ])->assertRedirect(route('admin.testimonials.index'));

    $testimonial = Testimonial::query()->where('client_name', 'أحمد')->firstOrFail();

    expect($testimonial->images()->where('collection', 'avatar')->exists())->toBeTrue();

    Storage::disk('custom')->assertExists($testimonial->image('avatar')->firstOrFail()->path);
});

test('tenant marketing opt-in updates tenant and flushes cache', function () {
    Storage::fake('custom');

    $tenant = Tenant::factory()->active()->create([
        'slug' => 'ibtikar',
        'name' => 'شركة الابتكار',
        'show_on_marketing' => false,
    ]);

    Cache::put(MarketingCache::PAGE_HOME, ['stale' => true], 600);

    $this->put(route('admin.tenants.marketing', $tenant->slug), [
        'show_on_marketing' => true,
        'marketing_logo' => UploadedFile::fake()->create('tenant.png', 100, 'image/png'),
        'alt_text' => 'شعار المستأجر',
    ])->assertRedirect(route('admin.tenants.show', $tenant->slug));

    $tenant->refresh();

    expect($tenant->show_on_marketing)->toBeTrue()
        ->and($tenant->images()->where('collection', 'logo')->exists())->toBeTrue()
        ->and(Cache::has(MarketingCache::PAGE_HOME))->toBeFalse();

    $partners = app(MarketingContent::class)->partners();
    expect($partners['names'])->toContain('شركة الابتكار');
});
