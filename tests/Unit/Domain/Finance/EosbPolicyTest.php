<?php

use App\Domain\Finance\Support\EosbPolicy;

/*
 * A true unit test: EosbPolicy touches no framework, no database and no clock,
 * so it needs none of tests/Pest.php's TestCase wiring. The calculator's own
 * arithmetic stays in tests/Feature/Tenant alongside the rest of the module,
 * matching where OffboardingSettlementTest already keeps it.
 */

test('a policy round-trips through its array form unchanged', function () {
    $policy = EosbPolicy::default();

    expect(EosbPolicy::fromArray($policy->toArray())->toArray())->toBe($policy->toArray());
});

test('missing keys fall back to defaults rather than throwing', function () {
    // A snapshot written before a field existed must still reconstitute — an
    // old settlement has to keep rendering.
    $policy = EosbPolicy::fromArray(['lower_tier_bps' => 2_500]);

    expect($policy->lowerTierBps)->toBe(2_500)
        ->and($policy->upperTierBps)->toBe(EosbPolicy::DEFAULT_UPPER_TIER_BPS)
        ->and($policy->tierBoundaryMonths)->toBe(EosbPolicy::DEFAULT_TIER_BOUNDARY_MONTHS)
        ->and($policy->nominalMonthDays)->toBe(EosbPolicy::DEFAULT_NOMINAL_MONTH_DAYS)
        ->and($policy->resignationTaper)->toBe(EosbPolicy::defaultResignationTaper());
});

test('an empty or malformed taper falls back to the default bands', function () {
    expect(EosbPolicy::fromArray(['resignation_taper' => []])->resignationTaper)
        ->toBe(EosbPolicy::defaultResignationTaper())
        ->and(EosbPolicy::fromArray(['resignation_taper' => 'nonsense'])->resignationTaper)
        ->toBe(EosbPolicy::defaultResignationTaper())
        // Rows missing a key are dropped; if none survive, the defaults stand.
        ->and(EosbPolicy::fromArray(['resignation_taper' => [['months' => 12]]])->resignationTaper)
        ->toBe(EosbPolicy::defaultResignationTaper());
});

test('taper bands are sorted ascending and coerced to non negative integers', function () {
    $policy = EosbPolicy::fromArray([
        'resignation_taper' => [
            ['months' => '60', 'bps' => '6667'],
            ['months' => -5, 'bps' => -100],
            ['months' => '24', 'bps' => 3_333],
        ],
    ]);

    expect($policy->resignationTaper)->toBe([
        ['months' => 0, 'bps' => 0],
        ['months' => 24, 'bps' => 3_333],
        ['months' => 60, 'bps' => 6_667],
    ]);
});

test('the payable band is the highest threshold reached', function () {
    $policy = EosbPolicy::default();

    expect($policy->resignationTaperBps(0))->toBe(0)
        ->and($policy->resignationTaperBps(23))->toBe(0)
        ->and($policy->resignationTaperBps(24))->toBe(3_333)
        ->and($policy->resignationTaperBps(59))->toBe(3_333)
        ->and($policy->resignationTaperBps(60))->toBe(6_667)
        ->and($policy->resignationTaperBps(119))->toBe(6_667)
        ->and($policy->resignationTaperBps(120))->toBe(10_000)
        ->and($policy->resignationTaperBps(600))->toBe(10_000);
});

test('a taper with no zero band pays nothing below its lowest threshold', function () {
    $policy = EosbPolicy::fromArray([
        'resignation_taper' => [['months' => 24, 'bps' => 10_000]],
    ]);

    expect($policy->resignationTaperBps(23))->toBe(0)
        ->and($policy->resignationTaperBps(24))->toBe(10_000);
});

test('a nominal month can never be zero, which would divide by zero downstream', function () {
    $policy = EosbPolicy::fromArray([
        'nominal_month_days' => 0,
        'nominal_day_hours' => 0,
    ]);

    expect($policy->nominalMonthDays)->toBe(1)
        ->and($policy->nominalDayHours)->toBe(1);
});
