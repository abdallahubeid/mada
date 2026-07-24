<?php

namespace App\Models;

use App\Models\Concerns\HasImages;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;

/**
 * Landing-page problem card (pain points section).
 *
 * @property int $id
 * @property string $title
 * @property string $description
 * @property string|null $icon_key
 * @property int $sort_order
 * @property bool $is_published
 */
class Problem extends Model
{
    use HasImages;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'title',
        'description',
        'icon_key',
        'sort_order',
        'is_published',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'sort_order' => 0,
        'is_published' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopePublished(Builder $query): Builder
    {
        return $query->where('is_published', true)->orderBy('sort_order');
    }
}
