<?php

namespace App\Services\Finance;

use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Finance\Support\EosbPolicy;
use App\Domain\Finance\Support\SettlementBreakdown;
use App\Domain\Tenancy\Enums\PayBasis;
use Illuminate\Support\Carbon;

/**
 * Computes an end-of-service settlement (BR-606).
 *
 * Pure: no Eloquent, no clock, no tenant context — every input is passed in,
 * exactly like {@see PayslipCalculator}, and for the same reason. All
 * arithmetic is integer minor units with exact half-up rounding (ADR-20).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ON THE EOSB FORMULA — READ BEFORE CHANGING
 *
 * End-of-service benefit is a STATUTORY entitlement and its rules differ by
 * jurisdiction. `docs/` specifies none: BR-606 requires "final settlement
 * calculation (unused leave payout + prorated final pay)" and stops there.
 *
 * The rates therefore do NOT live here. They arrive as an {@see EosbPolicy},
 * which each tenant configures in `finance_settings` and which the settlement
 * snapshots at generation. Purity is why the policy is a parameter rather than
 * a lookup: this class must remain callable with no database behind it.
 *
 * Omitting the policy falls back to {@see EosbPolicy::default()} — the
 * GCC/Saudi pattern these constants previously hardcoded, unchanged, so a
 * tenant that never configures anything computes what it always did.
 * ─────────────────────────────────────────────────────────────────────────
 */
final class OffboardingCalculator
{
    /**
     * @param  int  $baseRate  minor units; monthly gross if salaried, hourly rate if hourly
     * @param  int  $unusedLeaveDays  from the employee's remaining balance
     * @param  int  $workedDaysInFinalMonth  calendar workdays actually served in the final month
     * @param  int  $scheduledDaysInFinalMonth  the org's working days in that month
     * @param  int  $loanDeduction  positive magnitude; outstanding advances to recover
     * @param  int  $otherDeduction  positive magnitude
     * @param  EosbPolicy|null  $policy  the tenant's configured rules; defaults
     *                                   to {@see EosbPolicy::default()}
     */
    public function calculate(
        PayBasis $payBasis,
        int $baseRate,
        int $serviceMonths,
        OffboardingReason $reason,
        int $unusedLeaveDays,
        int $workedDaysInFinalMonth = 0,
        int $scheduledDaysInFinalMonth = 0,
        int $loanDeduction = 0,
        int $otherDeduction = 0,
        ?EosbPolicy $policy = null,
    ): SettlementBreakdown {
        $policy ??= EosbPolicy::default();

        $monthlyWage = $this->monthlyWage($payBasis, $baseRate, $policy);

        $eosb = $this->eosb($monthlyWage, $serviceMonths, $reason, $policy);
        $leavePayout = $this->leavePayout($monthlyWage, $unusedLeaveDays, $scheduledDaysInFinalMonth, $policy);
        $proratedSalary = $this->proratedSalary($monthlyWage, $workedDaysInFinalMonth, $scheduledDaysInFinalMonth);

        // Deductions are stored as their effect on the payout: always <= 0.
        $loan = -abs($loanDeduction);
        $other = -abs($otherDeduction);

        return new SettlementBreakdown(
            eosbAmount: $eosb,
            leavePayoutAmount: $leavePayout,
            proratedSalaryAmount: $proratedSalary,
            loanDeductionAmount: $loan,
            otherDeductionAmount: $other,
            totalAmount: $eosb + $leavePayout + $proratedSalary + $loan + $other,
        );
    }

    /**
     * Months of continuous service, floored — a partial month does not accrue.
     */
    public function serviceMonths(?Carbon $joiningDate, Carbon $lastWorkingDay): int
    {
        if ($joiningDate === null || $joiningDate->greaterThan($lastWorkingDay)) {
            return 0;
        }

        return (int) $joiningDate->copy()->startOfDay()->diffInMonths($lastWorkingDay->copy()->startOfDay());
    }

    /**
     * An hourly employee has no "monthly wage" of their own, so EOSB and leave
     * payout are derived from the tenant's nominal working month at their rate.
     * The alternative — averaging actual earnings — needs a payslip history the
     * settlement may not have, and would produce a different number for two
     * employees on identical contracts.
     */
    private function monthlyWage(PayBasis $payBasis, int $baseRate, EosbPolicy $policy): int
    {
        return match ($payBasis) {
            PayBasis::Unpaid => 0,
            PayBasis::Hourly => $baseRate * $policy->nominalDayHours * $policy->nominalMonthDays,
            PayBasis::Salaried => $baseRate,
        };
    }

    private function eosb(int $monthlyWage, int $serviceMonths, OffboardingReason $reason, EosbPolicy $policy): int
    {
        if (! $policy->enabled || $monthlyWage <= 0 || $serviceMonths <= 0) {
            return 0;
        }

        $lowerMonths = min($serviceMonths, $policy->tierBoundaryMonths);
        $upperMonths = max(0, $serviceMonths - $policy->tierBoundaryMonths);

        // Accrual is computed in months rather than whole years so a 30-month
        // service period earns its extra six months rather than being floored
        // to two years.
        $gross = $this->proportion($monthlyWage * $lowerMonths * $policy->lowerTierBps, 12 * EosbPolicy::BPS_DIVISOR)
            + $this->proportion($monthlyWage * $upperMonths * $policy->upperTierBps, 12 * EosbPolicy::BPS_DIVISOR);

        if ($reason !== OffboardingReason::Resignation) {
            return $gross;
        }

        return $this->proportion($gross * $policy->resignationTaperBps($serviceMonths), EosbPolicy::BPS_DIVISOR);
    }

    /**
     * Unused leave is paid at the daily rate implied by the working month.
     */
    private function leavePayout(int $monthlyWage, int $unusedLeaveDays, int $scheduledDaysInMonth, EosbPolicy $policy): int
    {
        if ($monthlyWage <= 0 || $unusedLeaveDays <= 0) {
            return 0;
        }

        $divisor = $scheduledDaysInMonth > 0 ? $scheduledDaysInMonth : $policy->nominalMonthDays;

        return $this->proportion($monthlyWage * $unusedLeaveDays, $divisor);
    }

    private function proratedSalary(int $monthlyWage, int $workedDays, int $scheduledDays): int
    {
        if ($monthlyWage <= 0 || $workedDays <= 0 || $scheduledDays <= 0) {
            return 0;
        }

        // Never pay more than a full month, however the inputs arrive.
        $workedDays = min($workedDays, $scheduledDays);

        return $this->proportion($monthlyWage * $workedDays, $scheduledDays);
    }

    /**
     * Exact integer half-up division — no float touches money (ADR-20).
     */
    private function proportion(int $numerator, int $denominator): int
    {
        if ($denominator === 0) {
            return 0;
        }

        $quotient = intdiv($numerator, $denominator);
        $remainder = $numerator % $denominator;

        return $remainder * 2 >= $denominator ? $quotient + 1 : $quotient;
    }
}
