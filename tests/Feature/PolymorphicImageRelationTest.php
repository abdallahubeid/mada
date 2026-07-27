<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Models\AiFeature;
use App\Models\Feature;
use App\Models\Image;
use App\Models\Module;
use App\Models\Offering;
use App\Models\Problem;
use App\Models\Solution;
use App\Models\Testimonial;
use App\Models\User;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('image model declares morphTo imageable relation', function () {
    expect((new Image)->imageable())->toBeInstanceOf(MorphTo::class);
});

test('all imageable entities expose morphMany and morphOne via HasImages', function (string $modelClass, string $collection) {
    $model = match ($modelClass) {
        Testimonial::class => Testimonial::factory()->create(),
        Tenant::class => Tenant::factory()->create(),
        User::class => User::factory()->create(),
        Solution::class => Solution::query()->create([
            'title' => 'حل',
            'description' => 'وصف',
            'icon' => 'ph:check-bold',
        ]),
        default => $modelClass::query()->create([
            'title' => 'عنوان',
            'description' => 'وصف',
            'icon_key' => 'shield',
        ]),
    };

    expect($model->images())->toBeInstanceOf(MorphMany::class)
        ->and($model->image($collection))->toBeInstanceOf(MorphOne::class);

    $image = $model->images()->create([
        'collection' => $collection,
        'disk' => 'custom',
        'path' => 'tests/'.$collection.'/sample.png',
        'alt_text' => 'اختبار',
    ]);

    expect($image->imageable_type)->toBe($model::class)
        ->and($image->imageable_id)->toBe($model->id)
        ->and($image->imageable()->is($model))->toBeTrue()
        ->and($model->fresh()->image($collection)->first()?->is($image))->toBeTrue()
        ->and($model->fresh()->images)->toHaveCount(1);
})->with([
    [Problem::class, 'icon'],
    [Solution::class, 'icon'],
    [Offering::class, 'icon'],
    [Module::class, 'icon'],
    [AiFeature::class, 'icon'],
    [Feature::class, 'icon'],
    [Testimonial::class, 'avatar'],
    [Tenant::class, 'logo'],
    [User::class, 'avatar'],
]);
