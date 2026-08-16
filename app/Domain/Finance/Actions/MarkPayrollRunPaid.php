<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\PayrollRunTransitionException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Notifications\Tenant\Finance\PayrollRunDisbursedNotification;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Support\Facades\DB;

/**
 * approved -> paid (BR-603).
 *
 * Records the FACT of disbursement. Mada does not move money: bank-file export
 * and payment-rail integration are explicitly unscoped (MADA_DOCS.md §16).
 *
 * This is the only mutation of a locked run the observer permits, and only
 * because it touches no figure — status and paid_at, nothing else.
 */
final class MarkPayrollRunPaid
{
    public function handle(PayrollRun $run): PayrollRun
    {
        if ($run->status !== PayrollRunStatus::Approved) {
            throw PayrollRunTransitionException::notInState(
                $run->period, 'disbursement', PayrollRunStatus::Approved, $run->status
            );
        }

        $run = DB::transaction(function () use ($run): PayrollRun {
            $run->update([
                'status' => PayrollRunStatus::Paid,
                'paid_at' => now(),
            ]);

            return $run->refresh();
        });

        $this->notifyEmployees($run);

        return $run;
    }

    /**
     * One notification per employee, carrying their OWN payslip.
     *
     * Deliberately not a single run-wide broadcast: the link has to land on the
     * only payslip that recipient is permitted to read (BR-614), and the net
     * figure in the message is personal. Employees with no linked user account
     * (HR-only profiles) are skipped by TenantNotifier::toEmployee().
     */
    private function notifyEmployees(PayrollRun $run): void
    {
        $notifier = app(TenantNotifier::class);

        $run->payslips()
            ->with('employee')
            ->get()
            ->each(function (Payslip $payslip) use ($notifier): void {
                $notifier->toEmployee($payslip->employee, new PayrollRunDisbursedNotification($payslip));
            });
    }
}
