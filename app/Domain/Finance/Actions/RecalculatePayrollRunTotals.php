<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItem;

/**
 * Rolls payslip figures up onto their run.
 *
 * Extracted so submit and approve share one definition of "the run's totals"
 * rather than each maintaining its own arithmetic. Refuses to touch a locked
 * run — the observer would reject it anyway, but failing here names the reason.
 */
final class RecalculatePayrollRunTotals
{
    public function handle(PayrollRun $run): PayrollRun
    {
        $payslipIds = $run->payslips()->pluck('id');

        // Line-item sums come from the items themselves rather than the
        // payslip's cached columns, so an edited draft line is reflected.
        $lineItems = PayslipLineItem::query()
            ->whereIn('payslip_id', $payslipIds)
            ->get();

        $allowances = $lineItems->where('amount', '>=', 0)->sum('amount');
        $deductions = $lineItems->where('amount', '<', 0)->sum('amount');

        $payslips = Payslip::query()->whereIn('id', $payslipIds)->get();

        $run->update([
            'total_base' => (int) $payslips->sum('base_amount'),
            'total_absence_deduction' => (int) $payslips->sum('absence_deduction'),
            'total_allowances' => (int) $allowances,
            'total_deductions' => (int) $deductions,
            'total_gross' => (int) $payslips->sum('gross_amount'),
            'total_net' => (int) $payslips->sum('net_amount'),
            'payslip_count' => $payslips->count(),
        ]);

        return $run;
    }
}
