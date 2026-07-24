<?php

namespace App\Domain\Tenancy\Middleware;

use App\Domain\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates every operational route (HR, Projects, Finance) behind an active tenant.
 *
 * Responsibilities, per docs/ARCHITECTURE.md §1.3 and §3 (BR-203):
 *
 *   1. Resolve the tenant for the authenticated user and bind it into
 *      {@see TenantContext} — the single source of truth every tenant-scoped
 *      query and every Spatie Teams permission check relies on.
 *   2. Reject the request unless that tenant's status is `active`. Tenants in
 *      `pending_verification` / `pending_approval` belong on the setup-wizard
 *      routes instead (which do not use this middleware); `suspended` and
 *      `cancelled` tenants are blocked outright.
 *
 * Register this middleware on every route group that exposes operational
 * (non-setup, non-public, non-Super-Admin) functionality.
 */
class EnsureTenantActive
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        $tenant = $user->tenant;

        abort_if($tenant === null, 403, 'This account is not associated with a tenant.');

        $this->tenantContext->setTenant($tenant);

        abort_unless(
            $tenant->isActive(),
            403,
            "This tenant is not active (current status: {$tenant->status->label()})."
        );

        return $next($request);
    }
}
