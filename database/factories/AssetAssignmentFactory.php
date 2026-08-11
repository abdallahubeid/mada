<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\AssetCondition;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\AssetAssignment;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<AssetAssignment>
 */
class AssetAssignmentFactory extends Factory
{
    protected $model = AssetAssignment::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'asset_id' => Asset::factory(),
            'employee_id' => Employee::factory(),
            'assigned_at' => now(),
            'returned_at' => null,
            'condition_on_assign' => AssetCondition::Good,
            'condition_on_return' => null,
            'notes' => fake()->optional()->sentence(),
            'assigned_by' => User::factory(),
        ];
    }
}
