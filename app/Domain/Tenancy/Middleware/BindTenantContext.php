<?php

namespace App\Domain\Tenancy\Middleware;

use App\Domain\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Bind the authenticated user's tenant into TenantContext (and Spatie team id)
 * without requiring the tenant to be active — used by the setup wizard.
 */
class BindTenantContext
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user !== null, 401);

        $tenant = $user->tenant;

        abort_if($tenant === null, 403, 'This account is not associated with a tenant.');

        $this->tenantContext->setTenant($tenant);

        return $next($request);
    }
}
