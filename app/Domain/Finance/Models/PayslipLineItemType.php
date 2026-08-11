<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A tenant-configurable allowance or deduction definition (BR-601).
 *
 * Deliberately data rather than an application enum, so a tenant can add
 * "housing allowance" or "GOSI deduction" without a code change.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string|null $code
 * @property PayslipLineItemKind $kind
 * @property int $default_amount
 * @property bool $is_active
 * @property bool $is_taxable
 * @property int $sort_order
 */
class PayslipLineItemType extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'default_amount' => 0,
        'is_active' => true,
        'is_taxable' => false,
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => PayslipLineItemKind::class,
            'default_amount' => 'integer',
            'is_active' => 'boolean',
            'is_taxable' => 'boolean',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Whether a signed amount is consistent with this type's kind.
     */
    public function permits(int $amount): bool
    {
        return $this->kind->permits($amount);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('is_active', true);
    }

    /**
     * @return HasMany<PayslipLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(PayslipLineItem::class);
    }
}
