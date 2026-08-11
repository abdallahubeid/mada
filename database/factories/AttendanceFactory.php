<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Carbon;

/**
 * @extends Factory<Attendance>
 */
class AttendanceFactory extends Factory
{
    protected $model = Attendance::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $date = fake()->dateTimeBetween('-30 days', 'now')->format('Y-m-d');

        return [
            'tenant_id' => Tenant::factory(),
            'employee_id' => Employee::factory(),
            'date' => $date,
            'check_in' => Carbon::parse("{$date} 08:55:00"),
            'check_out' => Carbon::parse("{$date} 17:05:00"),
            'status' => AttendanceStatus::Present,
            'notes' => null,
        ];
    }
}
