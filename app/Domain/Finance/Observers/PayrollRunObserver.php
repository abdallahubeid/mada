<?php

namespace App\Domain\Finance\Observers;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\LockedFinancialRecordException;
use App\Domain\Finance\Models\PayrollRun;

/**
 * Enforces payroll run immutability at the model layer (BR-610, NFR-11).
 *
 * This is the real boundary — routes and policies are only UX. It fires on
 * every write path that goes through a model instance, including ones added
 * years from now by someone who has never read BR-603.
 *
 * KNOWN LIMIT: Builder::update() and Builder::delete() fire no model events, so
 * a mass update bypasses this entirely. Every write to payroll tables must go
 * through model instances; there is a test asserting the guard holds on the
 * instance path.
 */
class PayrollRunObserver
{
    public function updating(PayrollRun $run): void
    {
        // getRawOriginal, not getOriginal: the latter applies the enum cast and
        // returns a PayrollRunStatus instance, which is not stringable.
        $original = PayrollRunStatus::tryFrom((string) $run->getRawOriginal('status'));

        if ($original === null || ! $original->isLocked()) {
            return;
        }

        if ($this->isLegalDisbursement($run, $original)) {
            return;
        }

        throw LockedFinancialRecordException::forRun($run->period, 'update');
    }

    /**
     * A locked run cannot be deleted at all — not even softly.
     *
     * BR-617 only requires blocking force-delete, but allowing a soft delete
     * would let an Owner trash an approved run, which frees `active_period`
     * and lets a second run claim the same month. Restoring the original would
     * then violate the unique key and fail. Blocking both keeps BR-611 and
     * BR-617 consistent with each other.
     */
    public function deleting(PayrollRun $run): void
    {
        if ($run->status->isLocked()) {
            throw LockedFinancialRecordException::forRun($run->period, 'deletion');
        }
    }

    public function forceDeleting(PayrollRun $run): void
    {
        if ($run->status->isLocked()) {
            throw LockedFinancialRecordException::forRun($run->period, 'permanent deletion');
        }
    }

    /**
     * Approved -> Paid is the one legal mutation of a locked run: it records
     * the fact of disbursement without touching a single figure.
     */
    private function isLegalDisbursement(PayrollRun $run, PayrollRunStatus $original): bool
    {
        if ($original !== PayrollRunStatus::Approved || $run->status !== PayrollRunStatus::Paid) {
            return false;
        }

        $changed = array_keys($run->getDirty());
        $permitted = ['status', 'paid_at', 'updated_at'];

        return array_diff($changed, $permitted) === [];
    }
}
