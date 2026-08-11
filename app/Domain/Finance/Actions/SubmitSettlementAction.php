<?php

namespace App\Domain\Finance\Actions;

use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Exceptions\SettlementException;
use App\Domain\Finance\Models\OffboardingSettlement;

/**
 * draft -> pending_approval (BR-606).
 */
final class SubmitSettlementAction
{
    public function handle(OffboardingSettlement $settlement): OffboardingSettlement
    {
        if (! $settlement->status->isEditable()) {
            throw SettlementException::notSubmittable($settlement->employee_name, $settlement->status);
        }

        $settlement->update(['status' => SettlementStatus::PendingApproval]);

        return $settlement->refresh();
    }
}
