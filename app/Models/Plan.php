<?php

namespace App\Models;

use Database\Factories\PlanFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * SaaS subscription plan (docs/MARKETING_CMS.md, DATABASE_ROADMAP.md).
 * Shared by public pricing and the admin plan console. Not tenant-scoped.
 *
 * @property int $id
 * @property string $name
 * @property string $slug
 * @property string|null $tagline
 * @property string|null $price_monthly
 * @property string|null $price_yearly
 * @property string $currency
 * @property string $cta_label
 * @property string $cta_url
 * @property bool $is_highlighted
 * @property bool $is_active
 * @property int $sort_order
 */
class Plan extends Model
{
    /** @use HasFactory<PlanFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'tagline',
        'price_monthly',
        'price_yearly',
        'currency',
        'cta_label',
        'cta_url',
        'is_highlighted',
        'is_active',
        'sort_order',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'price_monthly' => 'decimal:2',
            'price_yearly' => 'decimal:2',
            'is_highlighted' => 'boolean',
            'is_active' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return HasMany<PlanFeature, $this>
     */
    public function features(): HasMany
    {
        return $this->hasMany(PlanFeature::class)->orderBy('sort_order');
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true)->orderBy('sort_order');
    }
}
