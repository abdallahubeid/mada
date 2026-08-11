<?php

namespace App\Notifications\Tenant\Finance;

use App\Domain\Finance\Models\Payslip;
use App\Notifications\Tenant\TenantNotification;
use Illuminate\Support\Facades\Route;

/**
 * An employee's own payslip has been paid (BR-603, BR-614).
 *
 * Carries the individual payslip, not the run: the recipient is the employee,
 * and the link must land on the only payslip they are permitted to read.
 */
class PayrollRunDisbursedNotification extends TenantNotification
{
    public function __construct(public Payslip $payslip) {}

    protected function title(): string
    {
        return 'تم صرف راتبك';
    }

    protected function message(): string
    {
        $period = $this->payslip->payrollRun?->period ?? '';
        $net = number_format(abs($this->payslip->net_amount) / 100, 2);
        $currency = $this->payslip->pay_currency;

        return "تم صرف راتب فترة {$period} بصافي {$net} {$currency}. يمكنك عرض قسيمة الراتب وطباعتها.";
    }

    protected function url(): ?string
    {
        return Route::has('finance.payslips.show')
            ? route('finance.payslips.show', $this->payslip->id)
            : null;
    }

    protected function icon(): string
    {
        return 'payroll';
    }

    protected function severity(): string
    {
        return 'medium';
    }

    protected function type(): string
    {
        return 'payroll_run_disbursed';
    }
}
