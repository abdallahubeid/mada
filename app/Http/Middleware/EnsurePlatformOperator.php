<?php

namespace App\Http\Middleware;

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Domain\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Spatie\Permission\PermissionRegistrar;
use Symfony\Component\HttpFoundation\Response;

/**
 * Ensures the requester is an authenticated platform operator and binds Spatie
 * team context to the platform sentinel so platform roles/permissions resolve.
 */
class EnsurePlatformOperator
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PermissionRegistrar $permissionRegistrar,
    ) {}

    /**
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user === null) {
            return redirect()->guest(route('login'));
        }

        $this->tenantContext->setTenant(null);
        $this->permissionRegistrar->setPermissionsTeamId(PlatformPermissionCatalog::TEAM_ID);

        if (! $user->canAccessPlatformConsole()) {
            abort(Response::HTTP_FORBIDDEN);
        }

        return $next($request);
    }
}
