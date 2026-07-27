<?php

namespace Database\Factories;

use App\Models\SupportMessage;
use App\Models\SupportThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportMessage>
 */
class SupportMessageFactory extends Factory
{
    protected $model = SupportMessage::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'support_thread_id' => SupportThread::factory(),
            'sender_role' => SupportMessage::ROLE_CUSTOMER,
            'sender_name' => fake()->name(),
            'body' => fake()->paragraph(),
            'delivered_at' => now(),
            'read_at' => null,
        ];
    }

    public function admin(): static
    {
        return $this->state(fn (): array => [
            'sender_role' => SupportMessage::ROLE_ADMIN,
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
