<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

/**
 * Platform-global FAQ item for the public marketing site (docs/MARKETING_CMS.md).
 * Not tenant-scoped.
 *
 * @property int $id
 * @property string $category
 * @property string $question
 * @property string $answer
 * @property int $sort_order
 * @property bool $is_published
 */
class Faq extends Model
{
    /** @use HasFactory<\Database\Factories\FaqFactory> */
    use HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category',
        'question',
        'answer',
        'sort_order',
        'is_published',
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
