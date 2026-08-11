<?php

namespace App\Console\Commands\Tenancy;

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Console\Command;

/**
 * Re-syncs every tenant's default roles against the current
 * {@see TenantPermissionCatalog} definition.
 *
 * Run this after changing the catalog (new permission keys, updated role
 * buckets) so already-provisioned tenants pick up the change without a full
 * database re-seed. Safe to re-run: role sync is idempotent.
 */
class SyncTenantRolePermissionsCommand extends Command
{
    protected $signature = 'tenant:sync-role-permissions';

    protected $description = 'Re-sync default tenant roles (Owner, HR Manager, etc.) against the current permission catalog for every existing tenant';

    public function handle(SeedDefaultTenantRoles $seeder): int
    {
        $synced = 0;

        Tenant::query()
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($seeder, &$synced): void {
                $seeder->handle($tenant);
                $synced++;
            });

        $this->info("Synced role permissions for {$synced} tenant(s).");

        return self::SUCCESS;
    }
}
