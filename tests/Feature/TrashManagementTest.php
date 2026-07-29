<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Models\Problem;
use App\Services\Admin\TrashManager;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('trash index lists soft deleted resources', function () {
    $problem = Problem::query()->create([
        'title' => 'محذوف للسلة',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);
    $problem->delete();

    $this->get(route('admin.trash.index'))
        ->assertOk()
        ->assertSee('سلة المحذوفات', false)
        ->assertSee('محذوف للسلة', false)
        ->assertSee('المشاكل', false);
});

test('soft delete flashes undo restore url', function () {
    $problem = Problem::query()->create([
        'title' => 'حذف مع تراجع',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $this->delete(route('admin.problems.destroy', $problem))
        ->assertRedirect(route('admin.problems.index'));

    $flasher = session('flasher');

    expect($flasher)->toBeArray()
        ->and($flasher['undo_url'] ?? null)->toBe(route('admin.trash.restore', [
            'type' => 'problems',
            'id' => $problem->id,
        ]))
        ->and($flasher['undo_label'] ?? null)->toBe('تراجع');
});

test('trash restore revives a soft deleted problem and its images', function () {
    $problem = Problem::query()->create([
        'title' => 'استعادة مشكلة',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);

    $image = $problem->images()->create([
        'collection' => 'icon',
        'disk' => 'custom',
        'path' => 'problem/icon/trash-test.png',
        'alt_text' => 'icon',
    ]);

    $problem->images->each->delete();
    $problem->delete();

    $this->post(route('admin.trash.restore', ['type' => 'problems', 'id' => $problem->id]))
        ->assertRedirect();

    expect(Problem::query()->find($problem->id))->not->toBeNull()
        ->and($image->fresh()->trashed())->toBeFalse();
});

test('trash force destroy permanently removes the record', function () {
    $problem = Problem::query()->create([
        'title' => 'حذف نهائي',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);
    $problem->delete();

    $this->delete(route('admin.trash.force-destroy', ['type' => 'problems', 'id' => $problem->id]))
        ->assertRedirect(route('admin.trash.index'));

    expect(Problem::withTrashed()->find($problem->id))->toBeNull();
});

test('trash bulk restore selected items', function () {
    $first = Problem::query()->create([
        'title' => 'أول',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);
    $second = Problem::query()->create([
        'title' => 'ثاني',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 2,
        'is_published' => true,
    ]);
    $first->delete();
    $second->delete();

    $this->post(route('admin.trash.restore-selected'), [
        'items' => [
            'problems:'.$first->id,
            'problems:'.$second->id,
        ],
    ])->assertRedirect(route('admin.trash.index'));

    expect(Problem::query()->whereKey([$first->id, $second->id])->count())->toBe(2);
});

test('trash empty permanently purges filtered items', function () {
    $problem = Problem::query()->create([
        'title' => 'تفريغ',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);
    $problem->delete();

    $this->delete(route('admin.trash.empty'), ['type' => 'problems'])
        ->assertRedirect(route('admin.trash.index'));

    expect(Problem::withTrashed()->find($problem->id))->toBeNull()
        ->and(app(TrashManager::class)->count('problems'))->toBe(0);
});

test('content manager can restore but cannot force delete from trash', function () {
    actingAsPlatformOperator(PlatformPermissionCatalog::ROLE_CONTENT_MANAGER);

    $problem = Problem::query()->create([
        'title' => 'صلاحيات السلة',
        'description' => 'وصف',
        'icon_key' => 'alert',
        'sort_order' => 1,
        'is_published' => true,
    ]);
    $problem->delete();

    $this->get(route('admin.trash.index'))->assertOk();

    $this->post(route('admin.trash.restore', ['type' => 'problems', 'id' => $problem->id]))
        ->assertRedirect();

    $problem->delete();

    $this->delete(route('admin.trash.force-destroy', ['type' => 'problems', 'id' => $problem->id]))
        ->assertForbidden();
});
