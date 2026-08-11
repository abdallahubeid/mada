<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use App\Services\Admin\PlatformNotificationPublisher;
use Illuminate\Support\Facades\DB;

/**
 * Advances a tenant from `pending_verification` to `pending_approval` once its
 * Owner confirms their email address (BR-202, ADR-05).
 *
 * This transition had no implementation. `VerifyEmailController::verify()`
 * called `$request->fulfill()`, which marks the USER verified and fires
 * `Verified` — but nothing listened, so the TENANT sat in
 * `pending_verification` forever. Every downstream rule keys off the tenant
 * status, not the user's, so the account was unreviewable: ApproveTenantAction
 * accepts only `pending_approval`, and the Super Admin had no way to reach it.
 *
 * Deliberately idempotent. A signed verification link can be opened twice, and
 * a second visit must not disturb a tenant a Super Admin has since approved,
 * rejected or suspended — only `pending_verification` advances.
 */
final class VerifyTenantEmailAction
{
    public function __construct(private readonly PlatformNotificationPublisher $notifications) {}

    /**
     * @return bool whether this call performed the transition
     */
    public function handle(Tenant $tenant, User $verifier): bool
    {
        if ($tenant->status !== TenantStatus::PendingVerification) {
            return false;
        }

        /*
         * Only the registering Owner's verification advances the account.
         * `Verified` fires for any user, so without this an employee confirming
         * their address would push the whole tenant into the review queue on
         * the Owner's behalf. Resolved as the tenant's oldest user, matching
         * how the review and suspension actions identify the Owner.
         */
        if (! $this->isOwnerOf($tenant, $verifier)) {
            return false;
        }

        DB::transaction(function () use ($tenant): void {
            $tenant->forceFill(['status' => TenantStatus::PendingApproval])->save();
        });

        $tenant->refresh();

        /*
         * The Super Admin is told HERE rather than at registration. Publishing
         * on signup announced "a tenant is awaiting your review" about a record
         * that was still `pending_verification` — the body rendered that status
         * verbatim, and the approve action would have refused it. The queue is
         * only real once the email is confirmed.
         *
         * No platform audit entry: `platform_audit_logs` records operator
         * actions, and this one is performed by the customer.
         */
        $this->notifications->tenantRegisteredPendingApproval($tenant);

        return true;
    }

    private function isOwnerOf(Tenant $tenant, User $user): bool
    {
        $owner = User::query()
            ->where('tenant_id', $tenant->id)
            ->oldest('id')
            ->first();

        return $owner !== null && $owner->is($user);
    }
}
