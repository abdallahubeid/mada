<?php

namespace App\Services\Media;

use App\Models\Image;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Str;

/**
 * Stores platform marketing images on the `custom` disk and mirrors rows in `images`.
 */
class ImageUploader
{
    public function store(
        Model $model,
        UploadedFile $file,
        string $collection = 'default',
        ?string $altText = null,
        bool $replace = true,
    ): Image {
        if ($replace) {
            $this->deleteCollection($model, $collection);
        }

        $directory = Str::of(class_basename($model))->lower()->append('/'.$collection)->toString();
        $path = $file->store($directory, 'custom');

        return $model->images()->create([
            'collection' => $collection,
            'disk' => 'custom',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'alt_text' => $altText,
            'sort_order' => 0,
        ]);
    }

    public function deleteCollection(Model $model, string $collection): void
    {
        $model->images()
            ->where('collection', $collection)
            ->get()
            ->each(fn (Image $image) => $image->delete());
    }

    public function syncDenormalizedPath(Model $model, string $attribute, ?Image $image): void
    {
        if (! in_array($attribute, $model->getFillable(), true)) {
            return;
        }

        $model->forceFill([
            $attribute => $image?->path,
        ])->save();
    }
}
