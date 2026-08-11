<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Exceptions\SettlementException;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Models\User;

/**
 * draft|pending_approval -> approved, and the settlement LOCKS (BR-606).
 */
final class ApproveSettlementAction
{
    public function handle(OffboardingSettlement $settlement, User $approver): OffboardingSettlement
    {
        if ($settlement->status !== SettlementStatus::PendingApproval) {
            throw SettlementException::notAwaitingApproval($settlement->employee_name, $settlement->status);
        }

        // BR-615, applied to settlements: a permission check cannot express
        // this, since the Owner bypass grants every ability implicitly.
        if (! $settlement->canBeApprovedBy($approver)) {
            throw SettlementException::authorCannotApprove($settlement->employee_name);
        }

        $settlement->update([
            'status' => SettlementStatus::Approved,
            'approver_id' => $approver->id,
            'approved_at' => now(),
        ]);

        return $settlement->refresh();
    }
}
