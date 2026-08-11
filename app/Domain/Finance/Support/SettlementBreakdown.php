<?php

namespace App\Domain\Finance\Support;

/**
 * The computed money on one offboarding settlement, in signed minor units.
 *
 * Same single sign rule as {@see PayslipTotals}: every value is its EFFECT ON
 * THE PAYOUT, so deductions are <= 0 and the total is a plain sum.
 */
final readonly class SettlementBreakdown
{
    public function __construct(
        public int $eosbAmount,
        public int $leavePayoutAmount,
        public int $proratedSalaryAmount,
        public int $loanDeductionAmount,
        public int $otherDeductionAmount,
        public int $totalAmount,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'eosb_amount' => $this->eosbAmount,
            'leave_payout_amount' => $this->leavePayoutAmount,
            'prorated_salary_amount' => $this->proratedSalaryAmount,
            'loan_deduction_amount' => $this->loanDeductionAmount,
            'other_deduction_amount' => $this->otherDeductionAmount,
            'total_amount' => $this->totalAmount,
        ];
    }
}
