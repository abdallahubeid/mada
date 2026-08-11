<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    protected $model = Department::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->words(2, true),
            'code' => strtoupper(fake()->unique()->bothify('DEP-###')),
            'description' => fake()->optional()->sentence(),
            'parent_id' => null,
            'manager_id' => null,
        ];
    }
}
