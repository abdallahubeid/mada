<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<LeaveRequest>
 */
class LeaveRequestFactory extends Factory
{
    protected $model = LeaveRequest::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(3)->startOfDay();
        $end = $start->copy()->addDays(2);

        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'leave_type_id' => LeaveType::factory(),
            'start_date' => $start->toDateString(),
            'end_date' => $end->toDateString(),
            'days_count' => 3,
            'reason' => fake()->optional()->sentence(),
            'status' => LeaveRequestStatus::Pending,
            'requires_manager_escalation' => false,
            'approval_level' => 1,
            'current_approval_level' => 0,
            'approved_by' => null,
            'rejection_reason' => null,
        ];
    }
}
