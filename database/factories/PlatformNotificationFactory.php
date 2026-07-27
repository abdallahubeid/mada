<?php

namespace Database\Factories;

use App\Models\PlatformNotification;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<PlatformNotification>
 */
class PlatformNotificationFactory extends Factory
{
    protected $model = PlatformNotification::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'category' => fake()->randomElement(PlatformNotification::CATEGORIES),
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(12),
            'target_url' => null,
            'read_at' => null,
        ];
    }

    public function unread(): static
    {
        return $this->state(fn (): array => [
            'read_at' => null,
        ]);
    }

    public function read(): static
    {
        return $this->state(fn (): array => [
            'read_at' => now(),
        ]);
    }

    public function approval(): static
    {
        return $this->state(fn (): array => [
            'category' => PlatformNotification::CATEGORY_APPROVAL,
        ]);
    }

    public function security(): static
    {
        return $this->state(fn (): array => [
            'category' => PlatformNotification::CATEGORY_SECURITY,
        ]);
    }

    public function jobFailed(): static
    {
        return $this->state(fn (): array => [
            'category' => PlatformNotification::CATEGORY_JOB_FAILED,
        ]);
    }

    public function ops(): static
    {
        return $this->state(fn (): array => [
            'category' => PlatformNotification::CATEGORY_OPS,
        ]);
    }
}
