<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Facades\Storage;

/**
 * Platform marketing/branding image (docs/ADMIN_CMS_ANALYSIS.md §2).
 * Not tenant-scoped — lives on the central `custom` disk.
 *
 * @property int $id
 * @property string $imageable_type
 * @property int $imageable_id
 * @property string $collection
 * @property string $disk
 * @property string $path
 * @property string|null $original_name
 * @property string|null $mime_type
 * @property int|null $file_size
 * @property string|null $alt_text
 * @property int $sort_order
 */
class Image extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'imageable_type',
        'imageable_id',
        'collection',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'file_size',
        'alt_text',
        'sort_order',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'disk' => 'custom',
        'collection' => 'default',
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'file_size' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function imageable(): MorphTo
    {
        return $this->morphTo();
    }

    public function url(): string
    {
        return Storage::disk($this->disk)->url($this->path);
    }

    protected static function booted(): void
    {
        // Soft delete keeps the file so restore can revive the media row.
        // Disk cleanup only runs on permanent removal.
        static::forceDeleting(function (Image $image): void {
            Storage::disk($image->disk)->delete($image->path);
        });
    }
}
