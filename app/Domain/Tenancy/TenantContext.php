<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use Spatie\Permission\PermissionRegistrar;

/**
 * Resolves and holds the tenant for the current request or process lifecycle.
 *
 * This is the single, authoritative source of "which tenant am I currently
 * operating as" for the entire application. Per docs/ARCHITECTURE.md §1.2:
 * no controller, Livewire component, or service may read `tenant_id` off
 * `Auth::user()` directly — everything resolves through this class instead.
 *
 * Bound as a singleton in AppServiceProvider, so the resolved tenant persists
 * for the lifetime of a single HTTP request. Queued jobs that touch
 * tenant-scoped data must explicitly re-bind the tenant on the worker via
 * {@see self::setTenant()} at the start of their `handle()` method, since a
 * queue worker has no "current request" to resolve from (docs/ARCHITECTURE.md
 * §1.2, point 3).
 */
class TenantContext
{
    private ?Tenant $tenant = null;

    /**
     * Bind the currently active tenant for this request/process.
     *
     * Also synchronizes Spatie Permission's "team" id so every role and
     * permission check is automatically scoped to this tenant, fulfilling
     * the Teams integration described in docs/ARCHITECTURE.md §2 (ADR-03).
     * Passing `null` (e.g. for a Super Admin or an unauthenticated request)
     * clears both the tenant and the active permissions team.
     */
    public function setTenant(?Tenant $tenant): void
    {
        $this->tenant = $tenant;

        if (app()->bound(PermissionRegistrar::class)) {
            app(PermissionRegistrar::class)->setPermissionsTeamId($tenant?->id);
        }
    }

    /**
     * The currently resolved tenant, or null if none is bound.
     */
    public function getTenant(): ?Tenant
    {
        return $this->tenant;
    }

    /**
     * The currently resolved tenant's id, or null if none is bound.
     *
     * This is the value every tenant-scoped query (via {@see TenantScope})
     * filters by.
     */
    public function getTenantId(): ?int
    {
        return $this->tenant?->id;
    }

    /**
     * Whether a tenant is currently bound to this context.
     */
    public function hasTenant(): bool
    {
        return $this->tenant !== null;
    }
}
