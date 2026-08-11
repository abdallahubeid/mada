<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Per-tenant organization settings (currency, timezone, setup progress).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $currency
 * @property string $timezone
 * @property EvaluationPeriodType $evaluation_periodicity
 * @property Carbon|null $setup_completed_at
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class OrgSetting extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'currency',
        'timezone',
        'evaluation_periodicity',
        'setup_completed_at',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'currency' => 'SAR',
        'timezone' => 'Asia/Riyadh',
        'evaluation_periodicity' => 'quarterly',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'setup_completed_at' => 'datetime',
            'evaluation_periodicity' => EvaluationPeriodType::class,
        ];
    }
}
