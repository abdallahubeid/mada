<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\PayrollRunTransitionException;
use App\Domain\Finance\Models\PayrollRun;
use App\Notifications\Tenant\Finance\PayrollRunSubmittedNotification;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Support\Facades\DB;

/**
 * draft -> pending_approval (BR-603).
 *
 * Recomputes the run's rolled-up totals from its payslips first: a draft may
 * have had line items edited since it was built, and the totals the approver
 * sees must be the ones they are approving.
 */
final class SubmitPayrollRunForApproval
{
    public function handle(PayrollRun $run): PayrollRun
    {
        if ($run->status !== PayrollRunStatus::Draft) {
            throw PayrollRunTransitionException::notInState($run->period, 'submit', PayrollRunStatus::Draft, $run->status);
        }

        if ($run->payslips()->count() === 0) {
            throw PayrollRunTransitionException::hasNoPayslips($run->period);
        }

        $run = DB::transaction(function () use ($run): PayrollRun {
            (new RecalculatePayrollRunTotals)->handle($run);

            $run->update(['status' => PayrollRunStatus::PendingApproval]);

            return $run->refresh();
        });

        /*
         * Routed by PERMISSION, not role name, so a custom tenant role holding
         * `finance.payroll.approve` is included automatically. The maker is
         * excluded — they are the one person who cannot act on this (BR-615),
         * and telling them their own run awaits their approval is noise.
         */
        app(TenantNotifier::class)->toPermission(
            (int) $run->tenant_id,
            'finance.payroll.approve',
            new PayrollRunSubmittedNotification($run),
            exceptUserId: $run->maker_id,
        );

        return $run;
    }
}
