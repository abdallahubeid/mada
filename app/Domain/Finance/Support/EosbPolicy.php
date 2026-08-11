<?php

namespace App\Domain\Finance\Support;

use App\Services\Finance\OffboardingCalculator;

/**
 * The end-of-service benefit rules one settlement is computed under (BR-606).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THIS EXISTS
 *
 * EOSB is a STATUTORY entitlement and its rules differ by jurisdiction. These
 * values used to be private constants on {@see OffboardingCalculator},
 * which made the assumption visible but left it uncorrectable: a tenant in a
 * jurisdiction with different rates had no way to say so, and EOSB is the
 * largest single payment most employees ever receive from an employer.
 *
 * They now live in `finance_settings`, per tenant, and reach the calculator as
 * this object. The calculator stays pure — no Eloquent, no tenant context —
 * because the policy is passed IN rather than looked up.
 *
 * The values returned by {@see self::default()} are the previous constants
 * exactly, so a tenant that never opens the settings screen computes what it
 * always did.
 *
 * NOT legal advice. The defaults implement the common GCC/Saudi pattern —
 * half a month's wage per year for the first five years, a full month per year
 * thereafter, tapered on resignation — and each tenant must confirm them
 * against the statute that applies to it.
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Rates are BASIS POINTS (1/100th of a percent), not money and not floats:
 * 5_000 bps = 50% = half a month's wage per year of service.
 */
final readonly class EosbPolicy
{
    public const BPS_DIVISOR = 10_000;

    /** Months of service after which the higher accrual rate applies. */
    public const DEFAULT_TIER_BOUNDARY_MONTHS = 60;

    /** Half a month's wage per year of service, in basis points. */
    public const DEFAULT_LOWER_TIER_BPS = 5_000;

    /** A full month's wage per year of service. */
    public const DEFAULT_UPPER_TIER_BPS = 10_000;

    /** Nominal working days in a month, when the real calendar is unknown. */
    public const DEFAULT_NOMINAL_MONTH_DAYS = 22;

    /** Nominal paid hours in a working day. */
    public const DEFAULT_NOMINAL_DAY_HOURS = 8;

    /**
     * @param  bool  $enabled  false zeroes EOSB entirely — for jurisdictions or
     *                         contract populations with no statutory entitlement.
     *                         Distinct from a 0% rate only in intent, but the
     *                         intent is what a reviewer needs to read.
     * @param  list<array{months: int, bps: int}>  $resignationTaper  ascending by
     *                                                                `months`; each entry is the portion payable from
     *                                                                that many months of service onward. Termination,
     *                                                                contract end and retirement always pay in full.
     * @param  int  $nominalMonthDays  divisor for an hourly employee's notional
     *                                 monthly wage, and the leave-payout fallback when
     *                                 the final month has no scheduled days.
     */
    public function __construct(
        public bool $enabled,
        public int $tierBoundaryMonths,
        public int $lowerTierBps,
        public int $upperTierBps,
        public array $resignationTaper,
        public int $nominalMonthDays,
        public int $nominalDayHours,
    ) {}

    /**
     * The previous hardcoded constants, unchanged.
     *
     * Used by any tenant with no `finance_settings` row, and as the seed value
     * when the settings screen is opened for the first time.
     */
    public static function default(): self
    {
        return new self(
            enabled: true,
            tierBoundaryMonths: self::DEFAULT_TIER_BOUNDARY_MONTHS,
            lowerTierBps: self::DEFAULT_LOWER_TIER_BPS,
            upperTierBps: self::DEFAULT_UPPER_TIER_BPS,
            resignationTaper: self::defaultResignationTaper(),
            nominalMonthDays: self::DEFAULT_NOMINAL_MONTH_DAYS,
            nominalDayHours: self::DEFAULT_NOMINAL_DAY_HOURS,
        );
    }

    /**
     * @return list<array{months: int, bps: int}>
     */
    public static function defaultResignationTaper(): array
    {
        return [
            ['months' => 0, 'bps' => 0],        // under 2 years: no entitlement
            ['months' => 24, 'bps' => 3_333],   // 2 to under 5 years: one third
            ['months' => 60, 'bps' => 6_667],   // 5 to under 10 years: two thirds
            ['months' => 120, 'bps' => 10_000], // 10 years or more: in full
        ];
    }

    /**
     * Rebuild from a stored snapshot or a settings row.
     *
     * Every field falls back to its default, so a snapshot written before a
     * field existed still reconstitutes rather than throwing — a settlement
     * from last year must keep rendering.
     *
     * @param  array<string, mixed>  $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            enabled: (bool) ($data['enabled'] ?? true),
            tierBoundaryMonths: (int) ($data['tier_boundary_months'] ?? self::DEFAULT_TIER_BOUNDARY_MONTHS),
            lowerTierBps: (int) ($data['lower_tier_bps'] ?? self::DEFAULT_LOWER_TIER_BPS),
            upperTierBps: (int) ($data['upper_tier_bps'] ?? self::DEFAULT_UPPER_TIER_BPS),
            resignationTaper: self::normalizeTaper($data['resignation_taper'] ?? null),
            nominalMonthDays: max(1, (int) ($data['nominal_month_days'] ?? self::DEFAULT_NOMINAL_MONTH_DAYS)),
            nominalDayHours: max(1, (int) ($data['nominal_day_hours'] ?? self::DEFAULT_NOMINAL_DAY_HOURS)),
        );
    }

    /**
     * @return array{enabled: bool, tier_boundary_months: int, lower_tier_bps: int, upper_tier_bps: int, resignation_taper: list<array{months: int, bps: int}>, nominal_month_days: int, nominal_day_hours: int}
     */
    public function toArray(): array
    {
        return [
            'enabled' => $this->enabled,
            'tier_boundary_months' => $this->tierBoundaryMonths,
            'lower_tier_bps' => $this->lowerTierBps,
            'upper_tier_bps' => $this->upperTierBps,
            'resignation_taper' => $this->resignationTaper,
            'nominal_month_days' => $this->nominalMonthDays,
            'nominal_day_hours' => $this->nominalDayHours,
        ];
    }

    /**
     * The portion of a full entitlement payable to someone who RESIGNED after
     * this many months.
     *
     * Bands are floors: the highest band whose threshold has been reached wins.
     * A taper with no band at 0 months pays nothing below its lowest threshold,
     * which is the intended reading of "no entitlement under two years".
     */
    public function resignationTaperBps(int $serviceMonths): int
    {
        $payable = 0;

        foreach ($this->resignationTaper as $band) {
            if ($serviceMonths >= $band['months']) {
                $payable = $band['bps'];
            }
        }

        return $payable;
    }

    /**
     * Coerce user-supplied or stored taper rows into ascending, integer bands.
     *
     * Sorting is not cosmetic: {@see self::resignationTaperBps()} takes the
     * last matching band, so an out-of-order array would silently pay the
     * wrong rate.
     *
     * @return list<array{months: int, bps: int}>
     */
    private static function normalizeTaper(mixed $taper): array
    {
        if (! is_array($taper) || $taper === []) {
            return self::defaultResignationTaper();
        }

        $bands = [];

        foreach ($taper as $band) {
            if (! is_array($band) || ! isset($band['months'], $band['bps'])) {
                continue;
            }

            $bands[] = [
                'months' => max(0, (int) $band['months']),
                'bps' => max(0, (int) $band['bps']),
            ];
        }

        if ($bands === []) {
            return self::defaultResignationTaper();
        }

        usort($bands, fn (array $a, array $b): int => $a['months'] <=> $b['months']);

        return array_values($bands);
    }
}
