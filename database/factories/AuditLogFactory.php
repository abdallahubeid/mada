<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\AuditLog;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AuditLog>
 */
class AuditLogFactory extends Factory
{
    protected $model = AuditLog::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => User::factory(),
            'action' => 'employee.created',
            'module' => 'hr',
            'subject_type' => null,
            'subject_id' => null,
            'changes' => ['name' => 'Example'],
            'ip_address' => '127.0.0.1',
        ];
    }
}
