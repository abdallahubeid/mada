<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Display bullet / optional limit row for a plan (docs/MARKETING_CMS.md).
 *
 * @property int $id
 * @property int $plan_id
 * @property string $label
 * @property int $sort_order
 * @property string|null $feature_key
 * @property string|null $value
 */
class PlanFeature extends Model
{
    use SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'plan_id',
        'label',
        'sort_order',
        'feature_key',
        'value',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'sort_order' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }
}
