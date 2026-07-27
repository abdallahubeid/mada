<?php

namespace Database\Factories;

use App\Models\NewsletterCampaign;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<NewsletterCampaign>
 */
class NewsletterCampaignFactory extends Factory
{
    protected $model = NewsletterCampaign::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'subject' => fake()->sentence(4),
            'content' => '<p>'.fake()->paragraph().'</p>',
            'recipients_count' => fake()->numberBetween(1, 50),
            'sent_at' => now()->subHours(fake()->numberBetween(1, 72)),
        ];
    }
}
