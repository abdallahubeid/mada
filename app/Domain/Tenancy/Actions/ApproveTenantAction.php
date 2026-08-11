<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Exceptions\TenantReviewException;
use App\Domain\Tenancy\Models\Tenant;
use App\Mail\Tenancy\TenantApprovedMail;
use App\Models\Plan;
use App\Models\User;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Super Admin approval of a pending tenant registration (BR-202).
 *
 * Activates the account, confirms the plan it runs on, and tells the owner.
 * Persistence commits before the mail goes out — a rolled-back approval that
 * had already emailed "your account is live" is unrecoverable from the
 * customer's side.
 */
final class ApproveTenantAction
{
    public function __construct(private readonly PlatformAuditor $auditor) {}

    /**
     * @param  int|null  $planId  confirms or overrides the plan chosen at
     *                            registration; null keeps whatever is on record
     */
    public function handle(Tenant $tenant, User $reviewer, ?int $planId = null): Tenant
    {
        $this->guardReviewable($tenant);

        $plan = $this->resolvePlan($tenant, $planId);

        DB::transaction(function () use ($tenant, $reviewer, $plan): void {
            $tenant->forceFill([
                'status' => TenantStatus::Active,
                'plan_id' => $plan?->id,
                // Keep the denormalised slug in step with the FK. Letting these
                // diverge is the failure mode the FK was introduced to end.
                'plan' => $plan?->slug ?? $tenant->plan,
                'activated_at' => now(),
                'reviewed_at' => now(),
                'reviewed_by' => $reviewer->id,
                // A previously rejected tenant that is later approved must not
                // keep carrying the reason it was once refused.
                'rejection_reason' => null,
            ])->save();
        });

        $tenant->refresh();

        $owner = $this->ownerOf($tenant);

        if ($owner !== null) {
            Mail::to($owner->email)->send(new TenantApprovedMail($tenant, $owner, $plan));
        }

        $this->auditor->log('tenant.approved', $tenant, [
            'tenant_id' => $tenant->id,
            'tenant_name' => $tenant->name,
            'plan_id' => $plan?->id,
            'plan_slug' => $plan?->slug,
            'reviewed_by' => $reviewer->id,
            'notified' => $owner?->email,
        ]);

        return $tenant;
    }

    /**
     * Only a tenant that has VERIFIED its email may be approved.
     *
     * Approving straight out of `pending_verification` would hand an active
     * workspace to an unconfirmed address, which is precisely the fake-signup
     * path ADR-05 exists to close.
     */
    private function guardReviewable(Tenant $tenant): void
    {
        if ($tenant->status === TenantStatus::Active) {
            throw TenantReviewException::alreadyActive($tenant->name);
        }

        if ($tenant->status !== TenantStatus::PendingApproval) {
            throw TenantReviewException::notAwaitingReview($tenant->name, $tenant->status);
        }
    }

    private function resolvePlan(Tenant $tenant, ?int $planId): ?Plan
    {
        if ($planId !== null) {
            return Plan::query()->findOrFail($planId);
        }

        if ($tenant->plan_id !== null) {
            return Plan::query()->find($tenant->plan_id);
        }

        return filled($tenant->plan)
            ? Plan::query()->where('slug', $tenant->plan)->first()
            : null;
    }

    /**
     * The Owner account that registered this tenant.
     *
     * Queried directly rather than through a role lookup so this does not
     * depend on the Spatie team context being bound — the Super Admin
     * approving is in a different tenant scope entirely.
     */
    private function ownerOf(Tenant $tenant): ?User
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->oldest('id')
            ->first();
    }
}
