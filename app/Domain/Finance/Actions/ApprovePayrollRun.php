<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\PayrollRunTransitionException;
use App\Domain\Finance\Models\PayrollRun;
use App\Models\User;
use App\Notifications\Tenant\Finance\PayrollRunApprovedNotification;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Support\Facades\DB;

/**
 * pending_approval -> approved, and the run LOCKS (BR-603, ADR-09).
 *
 * Ordering is load-bearing: totals are recalculated while the run is still
 * unlocked, and the status flip is the LAST write. Setting the status first
 * would make the observer reject the very snapshot the lock is supposed to
 * preserve (BR-608).
 */
final class ApprovePayrollRun
{
    public function handle(PayrollRun $run, User $approver): PayrollRun
    {
        if ($run->status !== PayrollRunStatus::PendingApproval) {
            throw PayrollRunTransitionException::notInState(
                $run->period, 'approval', PayrollRunStatus::PendingApproval, $run->status
            );
        }

        // BR-615. A permission check cannot express this: the Owner
        // Gate::before bypass grants every Owner `finance.payroll.prepare`,
        // so separation of duties has to live in the domain.
        if (! $run->canBeApprovedBy($approver)) {
            throw PayrollRunTransitionException::makerCannotApprove($run->period);
        }

        $run = DB::transaction(function () use ($run, $approver): PayrollRun {
            (new RecalculatePayrollRunTotals)->handle($run);

            $run->update([
                'status' => PayrollRunStatus::Approved,
                'approver_id' => $approver->id,
                'approved_at' => now(),
                'rejection_reason' => null,
            ]);

            return $run->refresh();
        });

        // Back to the maker specifically — this is the one recipient who needs
        // to know their run cleared and is now immutable.
        $maker = $run->maker_id !== null
            ? User::query()->whereKey($run->maker_id)->where('is_active', true)->get()
            : null;

        if ($maker !== null && $maker->isNotEmpty()) {
            app(TenantNotifier::class)->toUsers($maker, new PayrollRunApprovedNotification($run));
        }

        return $run;
    }
}
