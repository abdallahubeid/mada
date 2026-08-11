<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Models\Payslip;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

class PayslipController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function show(Payslip $payslip): View
    {
        $this->authorizeView($payslip);

        return view('tenant.finance.payslips.show', [
            'payslip' => $payslip->load(['lineItems', 'payrollRun', 'employee']),
        ]);
    }

    /**
     * Print view renders in a fixed light theme regardless of the app theme
     * (DESIGN_SYSTEM.md §2.2) — printed output should not depend on screen
     * appearance settings.
     */
    public function print(Payslip $payslip): View
    {
        $this->authorizeView($payslip);

        return view('tenant.finance.payslips.print', [
            'payslip' => $payslip->load(['lineItems', 'payrollRun', 'employee']),
        ]);
    }

    /**
     * BR-614: an employee may read only their OWN payslips, and only those on
     * an approved or paid run. Draft figures are the preparer's working copy.
     *
     * Enforced here rather than by route permission alone, because the two
     * audiences reach the same URL: finance staff viewing anyone's payslip, and
     * an employee viewing their own.
     */
    private function authorizeView(Payslip $payslip): void
    {
        abort_unless(
            (int) $payslip->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );

        $user = request()->user();

        if ($user?->can('finance.payroll.view_any')) {
            return;
        }

        $employeeId = Employee::query()->where('user_id', $user?->id)->value('id');

        abort_if($employeeId === null, 403);
        abort_unless((int) $payslip->employee_id === (int) $employeeId, 403);
        abort_unless($payslip->payrollRun?->status->isLocked() ?? false, 403);
    }
}
