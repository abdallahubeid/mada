<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Support\EosbPolicy;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Services\Finance\OffboardingCalculator;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Per-tenant finance configuration — a singleton, one row per tenant.
 *
 * Holds the end-of-service rules that used to be constants on
 * {@see OffboardingCalculator}. The calculator itself
 * never reads this model: {@see self::eosbPolicy()} produces a plain value
 * object which is passed in, so the calculator stays pure.
 *
 * @property int $id
 * @property int $tenant_id
 * @property bool $eosb_enabled
 * @property int $eosb_tier_boundary_months
 * @property int $eosb_lower_tier_bps
 * @property int $eosb_upper_tier_bps
 * @property list<array{months: int, bps: int}>|null $eosb_resignation_taper
 * @property int $nominal_month_days
 * @property int $nominal_day_hours
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class FinanceSetting extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'eosb_enabled',
        'eosb_tier_boundary_months',
        'eosb_lower_tier_bps',
        'eosb_upper_tier_bps',
        'eosb_resignation_taper',
        'nominal_month_days',
        'nominal_day_hours',
        'created_by',
        'updated_by',
    ];

    /**
     * Mirrors the column defaults, so an unsaved instance returned by
     * {@see self::current()} answers exactly as a freshly inserted row would.
     *
     * @var array<string, mixed>
     */
    protected $attributes = [
        'eosb_enabled' => true,
        'eosb_tier_boundary_months' => EosbPolicy::DEFAULT_TIER_BOUNDARY_MONTHS,
        'eosb_lower_tier_bps' => EosbPolicy::DEFAULT_LOWER_TIER_BPS,
        'eosb_upper_tier_bps' => EosbPolicy::DEFAULT_UPPER_TIER_BPS,
        'nominal_month_days' => EosbPolicy::DEFAULT_NOMINAL_MONTH_DAYS,
        'nominal_day_hours' => EosbPolicy::DEFAULT_NOMINAL_DAY_HOURS,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'eosb_enabled' => 'boolean',
            'eosb_tier_boundary_months' => 'integer',
            'eosb_lower_tier_bps' => 'integer',
            'eosb_upper_tier_bps' => 'integer',
            'eosb_resignation_taper' => 'array',
            'nominal_month_days' => 'integer',
            'nominal_day_hours' => 'integer',
        ];
    }

    /**
     * This tenant's settings, or an unsaved instance carrying the defaults.
     *
     * Deliberately does NOT create a row. A tenant that has never opened the
     * settings screen has made no decision about its EOSB rules, and writing a
     * row on first read would misrepresent the defaults as a configured
     * choice — including in the audit log.
     */
    public static function current(): self
    {
        return static::query()->first() ?? new static;
    }

    public function eosbPolicy(): EosbPolicy
    {
        return EosbPolicy::fromArray([
            'enabled' => $this->eosb_enabled,
            'tier_boundary_months' => $this->eosb_tier_boundary_months,
            'lower_tier_bps' => $this->eosb_lower_tier_bps,
            'upper_tier_bps' => $this->eosb_upper_tier_bps,
            'resignation_taper' => $this->eosb_resignation_taper,
            'nominal_month_days' => $this->nominal_month_days,
            'nominal_day_hours' => $this->nominal_day_hours,
        ]);
    }
}
