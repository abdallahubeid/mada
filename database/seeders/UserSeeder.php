<?php

namespace Database\Seeders;

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\PermissionRegistrar;

class UserSeeder extends Seeder
{
    public function run(): void
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        PlatformPermissionCatalog::bindTeam($registrar);

        try {
            DB::table(config('permission.table_names.model_has_roles'))->delete();
            DB::table(config('permission.table_names.model_has_permissions'))->delete();
            User::query()->withTrashed()->forceDelete();

            $user = User::query()->create([
                'tenant_id' => null,
                'name' => 'Super Admin',
                'email' => 'owner@mada.com',
                'password' => 'owner123456789',
                'email_verified_at' => now(),
            ]);

            $user->assignRole(PlatformPermissionCatalog::ROLE_SUPER_ADMIN);
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
