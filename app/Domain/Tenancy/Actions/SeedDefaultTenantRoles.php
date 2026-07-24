<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Spatie\Permission\Models\Role;

/**
 * Seeds the default role templates for a newly created tenant (BR-102,
 * docs/ARCHITECTURE.md §2.2). Every tenant starts with these five roles;
 * the Owner is the only one who may create additional custom roles (BR-103).
 *
 * Binds the tenant into {@see TenantContext} before creating the roles so
 * Spatie's Teams feature stamps each role with the correct `tenant_id`
 * (ADR-03) — see the package's Role::create() team-key auto-assignment.
 */
class SeedDefaultTenantRoles
{
    /**
     * @var list<string>
     */
    public const ROLES = [
        'Owner',
        'HR Manager',
        'Finance Manager',
        'Project Manager',
        'Employee',
    ];

    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Tenant $tenant): void
    {
        $this->tenantContext->setTenant($tenant);

        foreach (self::ROLES as $role) {
            Role::findOrCreate($role, 'web');
        }
    }
}
