<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveType>
 */
class LeaveTypeFactory extends Factory
{
    protected $model = LeaveType::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->unique()->randomElement(['سنوية', 'مرضية', 'عارضة', 'بدون راتب']).' '.fake()->numerify('##'),
            'annual_days' => 14,
            'requires_approval' => true,
        ];
    }
}
