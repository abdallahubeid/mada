<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Exceptions\TenantReviewException;
use App\Domain\Tenancy\Middleware\EnsureTenantActive;
use App\Domain\Tenancy\Models\Tenant;
use App\Mail\Tenancy\TenantSuspendedMail;
use App\Models\User;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Super Admin suspension of a live tenant (BR-203).
 *
 * This is the most consequential action the Platform Console exposes: it locks
 * every user of a paying customer out of every operational module at once
 * (enforced by {@see EnsureTenantActive}). So it carries the same shape as
 * rejection — a mandatory written reason, stored and mailed, not just logged.
 *
 * Suspension is reversible and repeatable, which is why it does NOT touch
 * `activated_at`. That column answers "was this account ever live", and a
 * suspended tenant was: clearing it would erase the approval that let them in.
 */
final class SuspendTenantAction
{
    public function __construct(private readonly PlatformAuditor $auditor) {}

    public function handle(Tenant $tenant, User $actor, string $reason): Tenant
    {
        $reason = trim($reason);

        if ($reason === '') {
            throw TenantReviewException::suspensionReasonRequired();
        }

        if ($tenant->status !== TenantStatus::Active) {
            throw TenantReviewException::notSuspendable($tenant->name, $tenant->status);
        }

        DB::transaction(function () use ($tenant, $actor, $reason): void {
            $tenant->forceFill([
                'status' => TenantStatus::Suspended,
                'suspended_at' => now(),
                'suspension_reason' => $reason,
                'suspended_by' => $actor->id,
            ])->save();
        });

        $tenant->refresh();

        // Persistence commits before the mail goes out, matching
        // ApproveTenantAction: a rolled-back suspension that had already told
        // the customer "your account is locked" is unrecoverable support-side.
        $owner = $this->ownerOf($tenant);

        if ($owner !== null) {
            Mail::to($owner->email)->send(new TenantSuspendedMail($tenant, $owner, $reason));
        }

        $this->auditor->log('tenant.suspended', $tenant, [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'reason' => $reason,
            'suspended_by' => $actor->id,
            'notified' => $owner?->email,
        ]);

        return $tenant;
    }

    /**
     * Queried directly rather than through a role lookup so this does not
     * depend on the Spatie team context being bound — the Super Admin acting
     * here is in a different tenant scope entirely.
     */
    private function ownerOf(Tenant $tenant): ?User
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->oldest('id')
            ->first();
    }
}
