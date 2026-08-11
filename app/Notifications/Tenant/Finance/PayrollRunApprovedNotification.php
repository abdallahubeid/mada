<?php

namespace App\Notifications\Tenant\Finance;

use App\Domain\Finance\Models\PayrollRun;
use App\Notifications\Tenant\TenantNotification;
use Illuminate\Support\Facades\Route;

/**
 * A payroll run has been approved and locked (BR-603).
 *
 * Routed back to the maker: they prepared it, they need to know it cleared and
 * that it is now immutable — any correction requires an adjustment entry in a
 * subsequent run rather than an edit.
 */
class PayrollRunApprovedNotification extends TenantNotification
{
    public function __construct(public PayrollRun $payrollRun) {}

    protected function title(): string
    {
        return 'تم اعتماد مسيرة الرواتب';
    }

    protected function message(): string
    {
        $period = $this->payrollRun->period;
        $net = number_format(abs($this->payrollRun->total_net) / 100, 2);
        $currency = $this->payrollRun->currency;

        return "تم اعتماد مسيرة رواتب {$period} بصافي {$net} {$currency}. أصبحت المسيرة مقفلة، وأي تصحيح يتم عبر قيد تسوية في مسيرة لاحقة.";
    }

    protected function url(): ?string
    {
        return Route::has('finance.payroll-runs.show')
            ? route('finance.payroll-runs.show', $this->payrollRun->id)
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
        return 'payroll_run_approved';
    }
}
