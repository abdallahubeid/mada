<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Employee>
 */
class EmployeeFactory extends Factory
{
    protected $model = Employee::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'user_id' => null,
            'department_id' => null,
            'manager_id' => null,
            'first_name' => fake()->firstName(),
            'last_name' => fake()->lastName(),
            'national_id' => fake()->optional()->numerify('1##########'),
            'phone' => fake()->optional()->numerify('+9665########'),
            'address' => fake()->optional()->address(),
            'avatar_path' => null,
            'cv_path' => null,
            'job_title' => fake()->jobTitle(),
            'joining_date' => fake()->dateTimeBetween('-3 years', 'now')->format('Y-m-d'),
            'status' => EmployeeStatus::Active,
        ];
    }
}
