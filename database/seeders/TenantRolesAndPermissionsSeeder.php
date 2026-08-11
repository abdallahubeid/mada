<?php

namespace Database\Seeders;

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\PermissionRegistrar;

/**
 * Syncs the tenant Spatie permission catalog and refreshes role grants
 * for every existing tenant (including newly added contact-message abilities).
 */
class TenantRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        try {
            $registrar->setPermissionsTeamId(null);
            TenantPermissionCatalog::syncCatalog();
            $registrar->forgetCachedPermissions();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }

        $seeder = app(SeedDefaultTenantRoles::class);

        Tenant::query()->orderBy('id')->each(function (Tenant $tenant) use ($seeder): void {
            $seeder->handle($tenant);
        });

        $this->command?->info('Tenant permissions synced — including tenant.trash.* and tenant.contact_messages.*');
    }
}
