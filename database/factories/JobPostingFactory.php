<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\EmploymentType;
use App\Domain\Tenancy\Enums\JobPostingStatus;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<JobPosting>
 */
class JobPostingFactory extends Factory
{
    protected $model = JobPosting::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $title = fake()->jobTitle();

        return [
            'tenant_id' => Tenant::factory(),
            'department_id' => null,
            'title' => $title,
            'slug' => Str::slug($title).'-'.fake()->unique()->numerify('###'),
            'employment_type' => EmploymentType::FullTime,
            'location' => fake()->optional()->city(),
            'salary_range' => fake()->optional()->randomElement(['8,000 - 12,000 SAR', 'Competitive']),
            'description' => fake()->paragraphs(2, true),
            'requirements' => implode("\n", fake()->sentences(4)),
            'status' => JobPostingStatus::Draft,
        ];
    }

    public function published(): static
    {
        return $this->state(fn (): array => [
            'status' => JobPostingStatus::Published,
        ]);
    }
}
