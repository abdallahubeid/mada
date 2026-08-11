<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Tenancy\Models\Employee;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Employee financial self-service (BR-614, BR-701).
 *
 * Scoped to the acting user's own employee record and to LOCKED runs only.
 * The employee_id is never read from the request — the same discipline the
 * rest of the self-service surface uses.
 */
class MyPayslipController extends Controller
{
    public function index(): View
    {
        $employeeId = $this->currentEmployeeId();

        // An admin with no linked employee profile gets a graceful notice
        // rather than a 403, matching the other self-service pages.
        $payslips = $employeeId === null
            ? Payslip::query()->whereRaw('1 = 0')->paginate(config('app.paginate_page'))
            : Payslip::query()
                ->with('payrollRun')
                ->where('employee_id', $employeeId)
                ->whereHas('payrollRun', fn ($query) => $query->whereIn('status', [
                    PayrollRunStatus::Approved->value,
                    PayrollRunStatus::Paid->value,
                ]))
                ->join('payroll_runs', 'payroll_runs.id', '=', 'payslips.payroll_run_id')
                ->orderByDesc('payroll_runs.period')
                ->select('payslips.*')
                ->paginate(config('app.paginate_page'));

        return view('tenant.finance.my-payslips.index', [
            'payslips' => $payslips,
            'hasEmployeeProfile' => $employeeId !== null,
            'lifetimeNet' => $employeeId === null ? 0 : $this->lifetimeNet($employeeId),
        ]);
    }

    private function lifetimeNet(int $employeeId): int
    {
        return (int) Payslip::query()
            ->where('employee_id', $employeeId)
            ->whereHas('payrollRun', fn ($query) => $query->where('status', PayrollRunStatus::Paid->value))
            ->sum('net_amount');
    }

    private function currentEmployeeId(): ?int
    {
        $id = Employee::query()->where('user_id', request()->user()?->id)->value('id');

        return $id === null ? null : (int) $id;
    }
}
