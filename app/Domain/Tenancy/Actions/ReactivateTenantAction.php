<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Exceptions\TenantReviewException;
use App\Domain\Tenancy\Models\Tenant;
use App\Mail\Tenancy\TenantReactivatedMail;
use App\Models\User;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Super Admin lifting of a suspension (BR-203).
 *
 * The strict inverse of {@see SuspendTenantAction}: it accepts ONLY `suspended`
 * and returns the tenant to `active`. It is deliberately not a general "make
 * this tenant active" switch — routing `cancelled` or `rejected` through here
 * would bypass the billing and review decisions those states record.
 *
 * The three suspension columns are cleared rather than kept, mirroring the way
 * ApproveTenantAction clears `rejection_reason`: a live tenant that still
 * advertised why it was once suspended would render that stale reason on the
 * console beside a green badge. The durable history stays in
 * `platform_audit_logs`.
 */
final class ReactivateTenantAction
{
    public function __construct(private readonly PlatformAuditor $auditor) {}

    public function handle(Tenant $tenant, User $actor): Tenant
    {
        if ($tenant->status !== TenantStatus::Suspended) {
            throw TenantReviewException::notSuspended($tenant->name, $tenant->status);
        }

        // Read before the update clears it — the audit entry has to record what
        // the tenant was suspended FOR, which is the one thing the reactivation
        // itself destroys.
        $liftedReason = $tenant->suspension_reason;
        $suspendedAt = $tenant->suspended_at;

        DB::transaction(function () use ($tenant): void {
            $tenant->forceFill([
                'status' => TenantStatus::Active,
                'suspended_at' => null,
                'suspension_reason' => null,
                'suspended_by' => null,
                /*
                 * `activated_at` is first-activation, not last: it is what
                 * distinguishes "was live at some point" from "never approved"
                 * (see RejectTenantAction). A tenant reaching this action was
                 * necessarily approved once, so it is only backfilled if some
                 * older row never recorded one.
                 */
                'activated_at' => $tenant->activated_at ?? now(),
            ])->save();
        });

        $tenant->refresh();

        $owner = $this->ownerOf($tenant);

        if ($owner !== null) {
            Mail::to($owner->email)->send(new TenantReactivatedMail($tenant, $owner));
        }

        $this->auditor->log('tenant.reactivated', $tenant, [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'lifted_reason' => $liftedReason,
            'suspended_at' => $suspendedAt?->toIso8601String(),
            'reactivated_by' => $actor->id,
            'notified' => $owner?->email,
        ]);

        return $tenant;
    }

    private function ownerOf(Tenant $tenant): ?User
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->oldest('id')
            ->first();
    }
}
