<?php

namespace App\Services\Finance;

use App\Domain\Finance\Support\PayslipTotals;
use App\Domain\Finance\Support\WorkLedgerSummary;
use App\Domain\Tenancy\Enums\PayBasis;

/**
 * Pure domain service: turns a contract's pay terms plus one employee's
 * reconciled Work Ledger counts into payslip money.
 *
 * Deliberately has NO database access, no Eloquent, no clock and no tenant
 * context — every input is passed in. That is what makes it exhaustively
 * testable without fixtures, and it is the only place pay arithmetic lives.
 *
 * ALL arithmetic is integer minor units (ADR-20). There is no float anywhere
 * in this class, including in rounding: {@see self::proportion()} does exact
 * integer half-up division, so a run of dozens of line items cannot drift.
 */
final class PayslipCalculator
{
    /**
     * @param  list<int>  $lineItemAmounts  signed minor units, effect on net pay
     */
    public function calculate(
        PayBasis $payBasis,
        int $baseRate,
        WorkLedgerSummary $ledger,
        array $lineItemAmounts = [],
    ): PayslipTotals {
        $baseAmount = $this->baseAmount($payBasis, $baseRate, $ledger);
        $absenceDeduction = $this->absenceDeduction($payBasis, $baseRate, $ledger);

        $allowancesTotal = 0;
        $deductionsTotal = 0;

        foreach ($lineItemAmounts as $amount) {
            if ($amount >= 0) {
                $allowancesTotal += $amount;

                continue;
            }

            $deductionsTotal += $amount;
        }

        $grossAmount = $baseAmount + $allowancesTotal;
        $netAmount = $grossAmount + $absenceDeduction + $deductionsTotal;

        return new PayslipTotals(
            baseAmount: $baseAmount,
            absenceDeduction: $absenceDeduction,
            allowancesTotal: $allowancesTotal,
            deductionsTotal: $deductionsTotal,
            grossAmount: $grossAmount,
            netAmount: $netAmount,
        );
    }

    /**
     * Base pay before allowances and deductions (BR-301).
     *
     * Salaried pay prorates by the employee's share of the period's working
     * days, so a mid-period joiner or leaver falls out of the ledger without a
     * separate proration calendar (BR-605).
     */
    private function baseAmount(PayBasis $payBasis, int $baseRate, WorkLedgerSummary $ledger): int
    {
        return match ($payBasis) {
            PayBasis::Unpaid => 0,

            // Hourly pay is earned per minute actually worked.
            PayBasis::Hourly => $this->proportion($baseRate * $ledger->workedMinutes, 60),

            PayBasis::Salaried => $ledger->periodScheduledDays > 0
                ? $this->proportion($baseRate * $ledger->scheduledDays, $ledger->periodScheduledDays)
                : $baseRate,
        };
    }

    /**
     * Absence deduction, always <= 0 (BR-602, BR-404).
     *
     * Sourced from the reconciled ledger's absent-day count, never from raw
     * attendance, so an approved leave day can never be deducted (BR-401).
     *
     * Only salaried pay carries a deduction. An hourly employee was never paid
     * for the absent time in the first place — deducting again would penalize
     * the same absence twice. Unpaid pay has nothing to deduct from.
     */
    private function absenceDeduction(PayBasis $payBasis, int $baseRate, WorkLedgerSummary $ledger): int
    {
        if ($payBasis !== PayBasis::Salaried) {
            return 0;
        }

        if ($ledger->periodScheduledDays <= 0 || $ledger->absentDays <= 0) {
            return 0;
        }

        return -$this->proportion($baseRate * $ledger->absentDays, $ledger->periodScheduledDays);
    }

    /**
     * Exact integer half-up division — the single rounding point (ADR-20).
     *
     * Uses intdiv plus a remainder comparison rather than round($a / $b) so no
     * float ever touches a monetary value. Callers pass non-negative operands;
     * sign is applied by the caller after rounding.
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
