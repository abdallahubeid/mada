<?php

namespace Database\Factories;

use App\Models\SupportThread;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportThread>
 */
class SupportThreadFactory extends Factory
{
    protected $model = SupportThread::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'email' => fake()->unique()->safeEmail(),
            'name' => fake()->name(),
            'company' => fake()->company(),
            'subject' => fake()->sentence(4),
            'status' => SupportThread::STATUS_OPEN,
            'last_message_at' => now(),
        ];
    }

    public function inProgress(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportThread::STATUS_IN_PROGRESS,
        ]);
    }

    public function resolved(): static
    {
        return $this->state(fn (): array => [
            'status' => SupportThread::STATUS_RESOLVED,
        ]);
    }
}
