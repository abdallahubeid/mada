<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\OfficialHoliday;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<OfficialHoliday>
 */
class OfficialHolidayFactory extends Factory
{
    protected $model = OfficialHoliday::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $start = now()->addDays(10)->startOfDay();

        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(2, true),
            'start_date' => $start->toDateString(),
            'end_date' => $start->toDateString(),
            'is_recurring' => false,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
