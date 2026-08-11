<?php

namespace App\Domain\Finance\Support;

/**
 * The computed money on one payslip, in signed minor units (ADR-20).
 *
 * Every value is expressed as its EFFECT ON NET PAY, so one sign rule covers
 * the whole object: positive adds, negative subtracts.
 *
 *   gross = base + allowances
 *   net   = gross + absenceDeduction + deductionsTotal
 *
 * `absenceDeduction` and `deductionsTotal` are therefore always <= 0.
 */
final readonly class PayslipTotals
{
    public function __construct(
        public int $baseAmount,
        public int $absenceDeduction,
        public int $allowancesTotal,
        public int $deductionsTotal,
        public int $grossAmount,
        public int $netAmount,
    ) {}

    /**
     * @return array<string, int>
     */
    public function toArray(): array
    {
        return [
            'base_amount' => $this->baseAmount,
            'absence_deduction' => $this->absenceDeduction,
            'allowances_total' => $this->allowancesTotal,
            'deductions_total' => $this->deductionsTotal,
            'gross_amount' => $this->grossAmount,
            'net_amount' => $this->netAmount,
        ];
    }
}
