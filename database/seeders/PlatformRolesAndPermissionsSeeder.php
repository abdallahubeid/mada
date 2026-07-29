<?php

namespace Database\Seeders;

use App\Domain\Platform\PlatformPermissionCatalog;
use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class PlatformRolesAndPermissionsSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();

        // Create global roles (roles.tenant_id = null).
        $registrar->setPermissionsTeamId(null);
        $registrar->forgetCachedPermissions();

        try {
            foreach (PlatformPermissionCatalog::all() as $permission) {
                Permission::findOrCreate($permission, PlatformPermissionCatalog::GUARD);
            }

            foreach (PlatformPermissionCatalog::rolePermissionMap() as $roleName => $permissions) {
                /** @var Role $role */
                $role = Role::findOrCreate($roleName, PlatformPermissionCatalog::GUARD);
                $role->syncPermissions($permissions);
            }
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
