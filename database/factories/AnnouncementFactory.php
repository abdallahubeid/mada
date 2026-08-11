<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\AnnouncementType;
use App\Domain\Tenancy\Models\Announcement;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Announcement>
 */
class AnnouncementFactory extends Factory
{
    protected $model = Announcement::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'title' => fake()->sentence(4),
            'content' => fake()->paragraph(),
            'type' => AnnouncementType::Info,
            'published_at' => now(),
            'expires_at' => null,
            'is_pinned' => false,
            'created_by' => User::factory(),
        ];
    }
}
