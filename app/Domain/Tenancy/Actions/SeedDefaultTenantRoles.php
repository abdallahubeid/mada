<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

/**
 * Seeds the default role templates for a newly created tenant (BR-102,
 * docs/ARCHITECTURE.md §2.2) and syncs {@see TenantPermissionCatalog} grants.
 *
 * Binds the tenant into {@see TenantContext} before creating the roles so
 * Spatie's Teams feature stamps each role with the correct `tenant_id`
 * (ADR-03). Permissions are global names; role assignments are team-scoped.
 */
class SeedDefaultTenantRoles
{
    /**
     * @var list<string>
     */
    public const ROLES = [
        TenantPermissionCatalog::ROLE_OWNER,
        TenantPermissionCatalog::ROLE_HR_MANAGER,
        TenantPermissionCatalog::ROLE_FINANCE_MANAGER,
        TenantPermissionCatalog::ROLE_PROJECT_MANAGER,
        TenantPermissionCatalog::ROLE_EMPLOYEE,
    ];

    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Tenant $tenant): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(null);

            TenantPermissionCatalog::syncCatalog();

            $this->tenantContext->setTenant($tenant);

            foreach (TenantPermissionCatalog::rolePermissionMap() as $roleName => $permissions) {
                /** @var Role $role */
                $role = Role::findOrCreate($roleName, TenantPermissionCatalog::GUARD);
                $role->syncPermissions($permissions);
            }

            $registrar->forgetCachedPermissions();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
