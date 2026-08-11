<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Creates a tenant and its Owner from a public registration (BR-201).
 *
 * Extracted from RegisterController so the same path is reachable from tests,
 * seeders and any future intake surface without duplicating the ordering
 * below — which is load-bearing:
 *
 *   1. Tenant first, because roles are Teams-scoped by `tenant_id` (ADR-03).
 *   2. Default roles before the Owner exists, so `assignRole('Owner')` has a
 *      role to bind to.
 *   3. TenantContext bound before `assignRole`, because Spatie resolves the
 *      team id from it — assigning outside the context silently writes a role
 *      row with a null team and the Owner ends up with no permissions.
 *
 * The tenant lands in `pending_verification`: email verification gates the
 * move to `pending_approval` (ADR-05), and only a Super Admin moves it to
 * `active` from there ({@see ApproveTenantAction}).
 */
final class RegisterTenantAction
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SeedDefaultTenantRoles $seedDefaultTenantRoles,
    ) {}

    /**
     * @param  array{company_name: string, company_slug: string, industry: ?string, team_size: ?string, plan: ?string, name: string, email: string, password: string}  $data
     * @return array{0: Tenant, 1: User}
     */
    public function handle(array $data): array
    {
        $tenant = null;

        $user = DB::transaction(function () use ($data, &$tenant): User {
            /*
             * The selected plan is captured as BOTH the FK and the legacy slug.
             * `plan_id` is the source of truth; `plan` remains a denormalised
             * read cache for SubscriptionOverview. Writing them together here
             * is what keeps them from drifting.
             */
            $plan = filled($data['plan'] ?? null)
                ? Plan::query()->where('slug', $data['plan'])->first()
                : null;

            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'slug' => $data['company_slug'],
                'status' => TenantStatus::PendingVerification,
                'industry' => $data['industry'] ?? null,
                'team_size' => $data['team_size'] ?? null,
                'plan' => $data['plan'] ?? null,
                'plan_id' => $plan?->id,
            ]);

            $this->seedDefaultTenantRoles->handle($tenant);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => null,
            ]);

            $this->tenantContext->setTenant($tenant);
            $user->assignRole('Owner');

            return $user;
        });

        return [$tenant, $user];
    }
}
