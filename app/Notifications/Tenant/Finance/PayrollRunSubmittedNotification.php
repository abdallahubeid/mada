<?php

namespace App\Notifications\Tenant\Finance;

use App\Domain\Finance\Models\PayrollRun;
use App\Notifications\Tenant\TenantNotification;
use Illuminate\Support\Facades\Route;

/**
 * A payroll run is awaiting approval (BR-603).
 *
 * Routed to holders of `finance.payroll.approve` — deliberately NOT to the
 * Finance Manager who prepared it, since they are the one thing that role
 * cannot do (BR-615).
 */
class PayrollRunSubmittedNotification extends TenantNotification
{
    public function __construct(public PayrollRun $payrollRun) {}

    protected function title(): string
    {
        return 'مسيرة رواتب بانتظار الاعتماد';
    }

    protected function message(): string
    {
        $period = $this->payrollRun->period;
        $count = $this->payrollRun->payslip_count;
        $net = number_format(abs($this->payrollRun->total_net) / 100, 2);
        $currency = $this->payrollRun->currency;

        return "مسيرة رواتب {$period} جاهزة للاعتماد — {$count} موظف بصافي {$net} {$currency}.";
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
        return 'high';
    }

    protected function type(): string
    {
        return 'payroll_run_submitted';
    }
}
