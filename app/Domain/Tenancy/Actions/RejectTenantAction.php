<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Exceptions\TenantReviewException;
use App\Domain\Tenancy\Models\Tenant;
use App\Mail\Tenancy\TenantRejectedMail;
use App\Models\User;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Super Admin refusal of a pending tenant registration (BR-202).
 *
 * The reason is mandatory and is stored on the tenant, not only mailed: the
 * applicant may ask why months later, and a Super Admin who did not make the
 * original decision needs to be able to answer.
 *
 * Rejection does NOT delete the tenant or its owner. The row is the record
 * that the application happened, `EnsureTenantActive` already blocks every
 * operational route for a rejected tenant, and a deleted account cannot be
 * reinstated if the refusal is later overturned.
 */
final class RejectTenantAction
{
    public function __construct(private readonly PlatformAuditor $auditor) {}

    public function handle(Tenant $tenant, User $reviewer, string $reason): Tenant
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw TenantReviewException::reasonRequired();
        }

        $this->guardReviewable($tenant);

        DB::transaction(function () use ($tenant, $reviewer, $reason): void {
            $tenant->forceFill([
                'status' => TenantStatus::Rejected,
                'rejection_reason' => $reason,
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                // Never approved, so it was never activated. Clearing this
                // keeps "activated_at is not null" a reliable test for
                // "this account was live at some point".
                'activated_at' => null,
            ])->save();
        });

        $tenant->refresh();

        $owner = $this->ownerOf($tenant);

        if ($owner !== null) {
            Mail::to($owner->email)->send(new TenantRejectedMail($tenant, $owner, $reason));
        }

        $this->auditor->log('tenant.rejected', $tenant, [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'reason' => $reason,
            'reviewed_by' => $reviewer->id,
            'notified' => $owner?->email,
        ]);

        return $tenant;
    }

    private function guardReviewable(Tenant $tenant): void
    {
        if ($tenant->status === TenantStatus::Active) {
            throw TenantReviewException::alreadyActive($tenant->name);
        }

        if ($tenant->status !== TenantStatus::PendingApproval) {
            throw TenantReviewException::notAwaitingReview($tenant->name, $tenant->status);
        }
    }

    private function ownerOf(Tenant $tenant): ?User
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->oldest('id')
            ->first();
    }
}
