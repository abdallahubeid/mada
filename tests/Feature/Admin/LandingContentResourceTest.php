<?php

use App\Models\AiFeature;
use App\Models\Feature;
use App\Models\Module;
use App\Models\Offering;
use App\Models\Problem;
use App\Models\Solution;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;

uses(RefreshDatabase::class);

test('admin landing content resource routes resolve', function (string $routePrefix) {
    $this->get(route($routePrefix.'.index'))->assertOk();
    $this->get(route($routePrefix.'.create'))->assertOk();
})->with([
    'admin.problems',
    'admin.solutions',
    'admin.offerings',
    'admin.modules',
    'admin.ai-features',
    'admin.features',
    'admin.testimonials',
]);

test('problem resource supports full crud with icon upload', function () {
    Storage::fake('custom');

    $this->post(route('admin.problems.store'), [
        'title' => 'مشكلة تجريبية',
        'description' => 'وصف المشكلة التجريبية',
        'icon_key' => 'link-break',
        'sort_order' => 1,
        'is_published' => true,
        'icon' => UploadedFile::fake()->create('icon.png', 50, 'image/png'),
        'alt_text' => 'أيقونة',
    ])->assertRedirect(route('admin.problems.index'));

    $problem = Problem::query()->where('title', 'مشكلة تجريبية')->firstOrFail();

    expect($problem->icon_key)->toBe('link-break')
        ->and($problem->images()->where('collection', 'icon')->exists())->toBeTrue();

    $this->get(route('admin.problems.edit', $problem))->assertOk();

    $this->put(route('admin.problems.update', $problem), [
        'title' => 'مشكلة محدّثة',
        'description' => 'وصف محدّث',
        'icon_key' => 'alert',
        'sort_order' => 2,
        'is_published' => false,
    ])->assertRedirect(route('admin.problems.index'));

    expect($problem->fresh()->title)->toBe('مشكلة محدّثة')
        ->and($problem->fresh()->is_published)->toBeFalse();

    $this->delete(route('admin.problems.destroy', $problem))
        ->assertRedirect(route('admin.problems.index'));

    expect(Problem::query()->whereKey($problem->id)->exists())->toBeFalse();
});

test('solution resource stores optional button fields', function () {
    $this->post(route('admin.solutions.store'), [
        'title' => 'حل متكامل',
        'description' => 'وصف الحل',
        'btn_text' => 'اكتشف المزيد',
        'btn_link' => '/features',
        'icon_key' => 'check',
        'sort_order' => 1,
        'is_published' => true,
    ])->assertRedirect(route('admin.solutions.index'));

    $solution = Solution::query()->where('title', 'حل متكامل')->firstOrFail();

    expect($solution->btn_text)->toBe('اكتشف المزيد')
        ->and($solution->btn_link)->toBe('/features');
});

test('remaining landing card resources can be stored', function (string $route, string $modelClass) {
    $this->post(route($route.'.store'), [
        'title' => 'عنصر تجريبي',
        'description' => 'وصف تجريبي',
        'icon_key' => 'star',
        'sort_order' => 3,
        'is_published' => true,
    ])->assertRedirect(route($route.'.index'));

    expect($modelClass::query()->where('title', 'عنصر تجريبي')->exists())->toBeTrue();
})->with([
    ['admin.offerings', Offering::class],
    ['admin.modules', Module::class],
    ['admin.ai-features', AiFeature::class],
    ['admin.features', Feature::class],
]);
