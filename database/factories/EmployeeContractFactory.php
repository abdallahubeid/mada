<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeContract>
 */
class EmployeeContractFactory extends Factory
{
    protected $model = EmployeeContract::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = fake()->dateTimeBetween('-1 year', 'now');

        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'contract_type' => ContractType::FullTime,
            'start_date' => $start->format('Y-m-d'),
            'end_date' => fake()->optional()->dateTimeBetween($start, '+1 year')?->format('Y-m-d'),
            'probation_end_date' => fake()->optional()->dateTimeBetween($start, '+90 days')?->format('Y-m-d'),
            'status' => ContractStatus::Active,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
