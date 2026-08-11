<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use App\Domain\Tenancy\Enums\EvaluationStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<EmployeeEvaluation>
 */
class EmployeeEvaluationFactory extends Factory
{
    protected $model = EmployeeEvaluation::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'evaluator_id' => null,
            'period_type' => EvaluationPeriodType::Quarterly,
            'period_key' => now()->format('Y').'-Q'.(int) ceil(now()->month / 3),
            'rating' => null,
            'notes' => null,
            'status' => EvaluationStatus::Draft,
        ];
    }
}
