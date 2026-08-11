<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One typed allowance or deduction on a payslip (BR-601).
 *
 * `label` and `kind` are snapshots of the type at creation time, so renaming
 * or deactivating a type later cannot alter a locked payslip (BR-608).
 *
 * `amount` is signed minor units expressed as its effect on net pay: an
 * allowance is positive, a deduction negative (ADR-20).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $payslip_id
 * @property int|null $payslip_line_item_type_id
 * @property string $label
 * @property PayslipLineItemKind $kind
 * @property int $amount
 * @property string|null $notes
 * @property int $sort_order
 */
class PayslipLineItem extends Model
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
        'sort_order' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'kind' => PayslipLineItemKind::class,
            'amount' => 'integer',
            'sort_order' => 'integer',
        ];
    }

    /**
     * Whether this line's sign agrees with its own kind.
     */
    public function hasConsistentSign(): bool
    {
        return $this->kind->permits($this->amount);
    }

    /**
     * @return BelongsTo<Payslip, $this>
     */
    public function payslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class);
    }

    /**
     * @return BelongsTo<PayslipLineItemType, $this>
     */
    public function type(): BelongsTo
    {
        return $this->belongsTo(PayslipLineItemType::class, 'payslip_line_item_type_id');
    }
}
