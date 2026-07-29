<?php

namespace App\Services\Admin;

use App\Domain\Platform\TrashableResourceCatalog;
use App\Models\Concerns\HasImages;
use App\Services\Marketing\MarketingCache;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Soft-delete recycle bin: list, restore, and permanent purge helpers.
 */
class TrashManager
{
    /**
     * @return Collection<int, array{
     *     type: string,
     *     type_label: string,
     *     id: int|string,
     *     title: string,
     *     subtitle: string|null,
     *     deleted_at: Carbon|null,
     *     restore_url: string,
     *     force_url: string
     * }>
     */
    public function items(?string $typeFilter = null): Collection
    {
        $resources = TrashableResourceCatalog::all();

        if ($typeFilter !== null) {
            if (! isset($resources[$typeFilter])) {
                return collect();
            }

            $resources = [$typeFilter => $resources[$typeFilter]];
        }

        $rows = collect();

        foreach ($resources as $type => $config) {
            /** @var class-string<Model> $modelClass */
            $modelClass = $config['model'];
            $query = $modelClass::onlyTrashed()->latest('deleted_at');

            if (isset($config['query'])) {
                $query = ($config['query'])($query);
            }

            foreach ($query->get() as $model) {
                $rows->push([
                    'type' => $type,
                    'type_label' => $config['label'],
                    'id' => $model->getKey(),
                    'title' => ($config['title'])($model),
                    'subtitle' => isset($config['subtitle']) ? ($config['subtitle'])($model) : null,
                    'deleted_at' => $model->deleted_at,
                    'restore_url' => route('admin.trash.restore', ['type' => $type, 'id' => $model->getKey()]),
                    'force_url' => route('admin.trash.force-destroy', ['type' => $type, 'id' => $model->getKey()]),
                ]);
            }
        }

        return $rows
            ->sortByDesc(fn (array $row) => $row['deleted_at']?->timestamp ?? 0)
            ->values();
    }

    public function count(?string $typeFilter = null): int
    {
        return $this->items($typeFilter)->count();
    }

    public function findTrashed(string $type, int|string $id): Model
    {
        TrashableResourceCatalog::assertSoftDeletable($type);
        $config = TrashableResourceCatalog::get($type);

        /** @var class-string<Model> $modelClass */
        $modelClass = $config['model'];
        $query = $modelClass::onlyTrashed()->whereKey($id);

        if (isset($config['query'])) {
            $query = ($config['query'])($query);
        }

        $model = $query->first();

        if ($model === null) {
            throw new InvalidArgumentException("Trashed [{$type}] #{$id} was not found.");
        }

        return $model;
    }

    public function restore(string $type, int|string $id): Model
    {
        $config = TrashableResourceCatalog::get($type);
        $model = $this->findTrashed($type, $id);

        DB::transaction(function () use ($model, $config, $type): void {
            $model->restore();

            if (($config['restore_images'] ?? false) && $this->usesHasImages($model)) {
                $model->images()->onlyTrashed()->restore();
            }

            if ($type === 'plans') {
                $model->forceFill(['is_active' => true])->save();
            }
        });

        if ($config['flush_marketing'] ?? false) {
            MarketingCache::flush();
        }

        return $model->fresh() ?? $model;
    }

    public function forceDestroy(string $type, int|string $id): void
    {
        $config = TrashableResourceCatalog::get($type);
        $model = $this->findTrashed($type, $id);

        DB::transaction(function () use ($model, $config, $type): void {
            if (($config['restore_images'] ?? false) && $this->usesHasImages($model)) {
                $model->images()->withTrashed()->get()->each->forceDelete();
            }

            if ($type === 'plans' && method_exists($model, 'features')) {
                $model->features()->withTrashed()->get()->each->forceDelete();
            }

            $model->forceDelete();
        });

        if ($config['flush_marketing'] ?? false) {
            MarketingCache::flush();
        }
    }

    /**
     * @param  list<array{type: string, id: int|string}>  $items
     */
    public function restoreMany(array $items): int
    {
        $restored = 0;

        foreach ($items as $item) {
            $this->restore($item['type'], $item['id']);
            $restored++;
        }

        return $restored;
    }

    /**
     * @param  list<array{type: string, id: int|string}>  $items
     */
    public function forceDestroyMany(array $items): int
    {
        $deleted = 0;

        foreach ($items as $item) {
            $this->forceDestroy($item['type'], $item['id']);
            $deleted++;
        }

        return $deleted;
    }

    public function empty(?string $typeFilter = null): int
    {
        $items = $this->items($typeFilter)->map(fn (array $row): array => [
            'type' => $row['type'],
            'id' => $row['id'],
        ])->all();

        return $this->forceDestroyMany($items);
    }

    public function flashSoftDeleted(string $message, string $type, Model $model): void
    {
        flash()->warning($message, [
            'undo_url' => route('admin.trash.restore', ['type' => $type, 'id' => $model->getKey()]),
            'undo_label' => 'تراجع',
            'undo_method' => 'POST',
        ]);
    }

    private function usesHasImages(Model $model): bool
    {
        return in_array(HasImages::class, class_uses_recursive($model), true);
    }
}
