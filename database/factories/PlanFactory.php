<?php

namespace Database\Factories;

use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    protected $model = Plan::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->word();

        return [
            'name' => ucfirst($name),
            'slug' => Str::slug($name),
            'tagline' => fake()->sentence(4),
            'price_monthly' => fake()->randomFloat(2, 29, 199),
            'price_yearly' => fake()->randomFloat(2, 19, 159),
            'currency' => 'USD',
            'cta_label' => 'ابدأ الآن',
            'cta_url' => '/register',
            'is_highlighted' => false,
            'is_active' => true,
            'sort_order' => 0,
        ];
    }
}
