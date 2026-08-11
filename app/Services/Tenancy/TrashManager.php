<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\TrashableResourceCatalog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Tenant soft-delete recycle bin: list, restore, and permanent purge helpers.
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

            if (in_array($type, ['leave-requests', 'contracts', 'evaluations'], true)) {
                $query->with(['employee' => fn ($relation) => $relation->withTrashed()]);
            }

            foreach ($query->get() as $model) {
                $subtitle = isset($config['subtitle']) ? ($config['subtitle'])($model) : null;

                $rows->push([
                    'type' => $type,
                    'type_label' => $config['label'],
                    'id' => $model->getKey(),
                    'title' => ($config['title'])($model),
                    'subtitle' => filled($subtitle) ? (string) $subtitle : null,
                    'deleted_at' => $model->deleted_at,
                    'restore_url' => route('tenant.trash.restore', ['type' => $type, 'id' => $model->getKey()]),
                    'force_url' => route('tenant.trash.force-delete', ['type' => $type, 'id' => $model->getKey()]),
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
        $model = $this->findTrashed($type, $id);

        DB::transaction(function () use ($model): void {
            $model->restore();
        });

        return $model->fresh() ?? $model;
    }

    public function forceDestroy(string $type, int|string $id): void
    {
        $model = $this->findTrashed($type, $id);

        DB::transaction(function () use ($model, $type): void {
            if ($type === 'contact-messages' && method_exists($model, 'messages')) {
                $model->messages()->withTrashed()->get()->each->forceDelete();
            }

            $model->forceDelete();
        });
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
            'undo_url' => route('tenant.trash.restore', ['type' => $type, 'id' => $model->getKey()]),
            'undo_label' => 'تراجع',
            'undo_method' => 'POST',
        ]);
    }

    /**
     * @return array{undo_url: string, undo_label: string, undo_method: string}
     */
    public function undoPayload(string $type, Model $model): array
    {
        return [
            'undo_url' => route('tenant.trash.restore', ['type' => $type, 'id' => $model->getKey()]),
            'undo_label' => 'تراجع',
            'undo_method' => 'POST',
        ];
    }
}
