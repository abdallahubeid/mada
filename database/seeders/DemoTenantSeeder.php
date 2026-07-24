<?php

namespace Database\Seeders;

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\User;
use Illuminate\Database\Seeder;

/**
 * Seeds one fully active demo tenant with an Owner user, for local
 * development and for manually exercising the tenant app shell
 * (docs/DEVELOPMENT_ROADMAP.md Phase 1).
 */
class DemoTenantSeeder extends Seeder
{
    public function run(): void
    {
        $tenant = Tenant::factory()->active()->create([
            'name' => 'Veyra Demo Co',
            'slug' => 'veyra-demo',
        ]);

        app(SeedDefaultTenantRoles::class)->handle($tenant);

        $owner = User::factory()->create([
            'tenant_id' => $tenant->id,
            'name' => 'Demo Owner',
            'email' => 'owner@veyra.test',
        ]);

        app(TenantContext::class)->setTenant($tenant);
        $owner->assignRole('Owner');

        $this->command?->info('Demo tenant ready — owner@veyra.test / password');
    }
}
