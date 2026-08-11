<?php

namespace Database\Factories;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantContactMessage;
use App\Domain\Tenancy\Models\TenantContactThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantContactMessage>
 */
class TenantContactMessageFactory extends Factory
{
    protected $model = TenantContactMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'tenant_contact_thread_id' => fn (array $attributes) => TenantContactThread::factory()->create([
                'tenant_id' => $attributes['tenant_id'],
            ])->id,
            'sender_role' => TenantContactMessage::ROLE_VISITOR,
            'sender_name' => fake()->name(),
            'body' => fake()->paragraph(),
            'delivered_at' => now(),
            'read_at' => null,
        ];
    }

    public function staff(): static
    {
        return $this->state(fn (): array => [
            'sender_role' => TenantContactMessage::ROLE_STAFF,
        ]);
    }

    public function pending(): static
    {
        return $this->state(fn (): array => [
            'delivered_at' => null,
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'delivered_at' => now()->subMinute(),
            'read_at' => now(),
        ]);
    }
}
