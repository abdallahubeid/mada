<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\TaskPriority;
use App\Domain\Tenancy\Enums\TaskStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Task;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Task>
 */
class TaskFactory extends Factory
{
    protected $model = Task::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'manager_id' => Employee::factory(),
            'employee_id' => Employee::factory(),
            'title' => fake()->sentence(4),
            'description' => fake()->optional()->paragraph(),
            'due_date' => now()->addDays(fake()->numberBetween(1, 30))->toDateString(),
            'priority' => TaskPriority::Medium,
            'status' => TaskStatus::Todo,
        ];
    }
}
