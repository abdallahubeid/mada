<?php

namespace App\Domain\Finance\Observers;

use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Exceptions\SettlementException;
use App\Domain\Finance\Models\OffboardingSettlement;

/**
 * An approved settlement is immutable (BR-606, NFR-11).
 *
 * Same shape as PayrollRunObserver, including the one permitted mutation:
 * approved -> paid records disbursement without touching a figure. A settlement
 * is typically the largest single payment an employee receives, so the same
 * discipline applies.
 */
class OffboardingSettlementObserver
{
    public function updating(OffboardingSettlement $settlement): void
    {
        $original = SettlementStatus::tryFrom((string) $settlement->getRawOriginal('status'));

        if ($original === null || ! $original->isLocked()) {
            return;
        }

        if ($this->isLegalDisbursement($settlement, $original)) {
            return;
        }

        throw SettlementException::locked($settlement->employee_name);
    }

    public function deleting(OffboardingSettlement $settlement): void
    {
        if ($settlement->status->isLocked()) {
            throw SettlementException::locked($settlement->employee_name);
        }
    }

    public function forceDeleting(OffboardingSettlement $settlement): void
    {
        if ($settlement->status->isLocked()) {
            throw SettlementException::locked($settlement->employee_name);
        }
    }

    private function isLegalDisbursement(OffboardingSettlement $settlement, SettlementStatus $original): bool
    {
        if ($original !== SettlementStatus::Approved || $settlement->status !== SettlementStatus::Paid) {
            return false;
        }

        return array_diff(array_keys($settlement->getDirty()), ['status', 'paid_at', 'updated_at']) === [];
    }
}
