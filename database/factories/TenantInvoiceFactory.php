<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\TenantInvoiceStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvoice;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TenantInvoice>
 */
class TenantInvoiceFactory extends Factory
{
    protected $model = TenantInvoice::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'number' => 'INV-'.fake()->unique()->numerify('######'),
            'amount' => fake()->randomFloat(2, 39, 199),
            'currency' => 'USD',
            'status' => TenantInvoiceStatus::Paid,
            'issued_at' => now()->subDays(fake()->numberBetween(1, 90)),
            'pdf_path' => null,
        ];
    }
}
