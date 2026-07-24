<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Tenant>
 */
class TenantFactory extends Factory
{
    protected $model = Tenant::class;

    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->company();

        return [
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'status' => TenantStatus::PendingVerification,
        ];
    }

    /**
     * Indicate that the tenant has verified its email and is awaiting Super Admin approval.
     */
    public function pendingApproval(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::PendingApproval,
        ]);
    }

    /**
     * Indicate that the tenant has been approved and is fully active.
     */
    public function active(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::Active,
        ]);
    }

    /**
     * Indicate that the tenant has been suspended.
     */
    public function suspended(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::Suspended,
        ]);
    }

    /**
     * Indicate that the tenant has been cancelled.
     */
    public function cancelled(): static
    {
        return $this->state(fn (array $attributes) => [
            'status' => TenantStatus::Cancelled,
        ]);
    }
}
