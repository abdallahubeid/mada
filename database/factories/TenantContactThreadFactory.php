<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantContactThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantContactThread>
 */
class TenantContactThreadFactory extends Factory
{
    protected $model = TenantContactThread::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'sender_name' => fake()->name(),
            'sender_email' => fake()->unique()->safeEmail(),
            'subject' => fake()->sentence(4),
            'status' => TenantContactThread::STATUS_OPEN,
            'last_message_at' => now(),
        ];
    }

    public function archived(): static
    {
        return $this->state(fn (): array => [
            'status' => TenantContactThread::STATUS_ARCHIVED,
        ]);
    }
}
