<?php

namespace Database\Factories;

use App\Models\Testimonial;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Testimonial>
 */
class TestimonialFactory extends Factory
{
    protected $model = Testimonial::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'quote' => fake()->paragraph(),
            'client_name' => fake()->name(),
            'client_role' => fake()->jobTitle(),
            'organization_name' => fake()->company(),
            'rate' => fake()->optional()->numberBetween(1, 5),
            'sort_order' => fake()->numberBetween(0, 100),
            'is_published' => true,
            'tenant_id' => null,
        ];
    }

    public function unpublished(): static
    {
        return $this->state(fn (array $attributes) => [
            'is_published' => false,
        ]);
    }
}
