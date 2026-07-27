<?php

namespace App\Models;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\Concerns\HasImages;
use Database\Factories\TestimonialFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Curated marketing testimonial (docs/MARKETING_CMS.md). Platform-global listing;
 * optional tenant_id is attribution only. Logos/avatars via {@see HasImages}.
 *
 * @property int $id
 * @property string $quote
 * @property string $client_name
 * @property string|null $client_role
 * @property string|null $organization_name
 * @property int|null $rate
 * @property int $sort_order
 * @property bool $is_published
 * @property int|null $tenant_id
 */
class Testimonial extends Model
{
    /** @use HasFactory<TestimonialFactory> */
    use HasFactory, HasImages, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'quote',
        'client_name',
        'client_role',
        'organization_name',
        'rate',
        'sort_order',
        'is_published',
        'tenant_id',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'rate' => 'integer',
            'sort_order' => 'integer',
            'is_published' => 'boolean',
        ];
    }

    /**
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
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
