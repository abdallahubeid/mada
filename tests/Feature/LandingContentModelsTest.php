<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Models\AiFeature;
use App\Models\Feature;
use App\Models\Module;
use App\Models\Offering;
use App\Models\Problem;
use App\Models\Solution;
use App\Models\Testimonial;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

test('landing content tables exist with expected columns', function () {
    expect(Schema::hasColumns('problems', ['title', 'description', 'icon_key', 'sort_order', 'is_published']))->toBeTrue()
        ->and(Schema::hasColumns('solutions', ['title', 'description', 'btn_text', 'btn_link', 'icon_key', 'sort_order', 'is_published']))->toBeTrue()
        ->and(Schema::hasColumns('offerings', ['title', 'description', 'icon_key', 'sort_order', 'is_published']))->toBeTrue()
        ->and(Schema::hasColumns('modules', ['title', 'description', 'icon_key', 'sort_order', 'is_published']))->toBeTrue()
        ->and(Schema::hasColumns('ai_features', ['title', 'description', 'icon_key', 'sort_order', 'is_published']))->toBeTrue()
        ->and(Schema::hasColumns('features', ['title', 'description', 'icon_key', 'sort_order', 'is_published']))->toBeTrue()
        ->and(Schema::hasColumn('testimonials', 'rate'))->toBeTrue()
        ->and(Schema::hasColumn('testimonials', 'logo_path'))->toBeFalse()
        ->and(Schema::hasColumn('tenants', 'show_on_marketing'))->toBeTrue()
        ->and(Schema::hasColumn('tenants', 'marketing_logo_path'))->toBeFalse();
});

test('landing content models persist and expose polymorphic images relation', function (string $modelClass) {
    /** @var Problem|Solution|Offering|Module|AiFeature|Feature $model */
    $model = $modelClass::query()->create([
        'title' => 'عنوان تجريبي',
        'description' => 'وصف تجريبي',
        'icon_key' => 'shield',
    ]);

    expect($model->is_published)->toBeTrue()
        ->and($model->sort_order)->toBe(0)
        ->and($model->images())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class)
        ->and($modelClass::query()->published()->whereKey($model)->exists())->toBeTrue();
})->with([
    Problem::class,
    Solution::class,
    Offering::class,
    Module::class,
    AiFeature::class,
    Feature::class,
]);

test('testimonial accepts rate and uses has images without logo path', function () {
    $testimonial = Testimonial::factory()->create(['rate' => 5]);

    expect($testimonial->rate)->toBe(5)
        ->and($testimonial->images())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class)
        ->and(array_key_exists('logo_path', $testimonial->getAttributes()))->toBeFalse();
});

test('tenant keeps show on marketing and uses has images', function () {
    $tenant = Tenant::factory()->create(['show_on_marketing' => true]);

    expect($tenant->show_on_marketing)->toBeTrue()
        ->and($tenant->images())->toBeInstanceOf(\Illuminate\Database\Eloquent\Relations\MorphMany::class)
        ->and(array_key_exists('marketing_logo_path', $tenant->getAttributes()))->toBeFalse();
});
