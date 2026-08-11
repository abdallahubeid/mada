<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\ApplicationStatus;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<JobApplication>
 */
class JobApplicationFactory extends Factory
{
    protected $model = JobApplication::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'job_posting_id' => JobPosting::factory(),
            'applicant_name' => fake()->name(),
            'email' => fake()->unique()->safeEmail(),
            'phone' => fake()->numerify('+9665########'),
            'cv_path' => 'tenant/1/applications/cvs/sample.pdf',
            'cover_letter' => fake()->optional()->paragraph(),
            'status' => ApplicationStatus::New,
            'converted_employee_id' => null,
        ];
    }
}
