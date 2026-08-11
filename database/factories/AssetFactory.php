<?php

namespace Database\Factories;

use App\Domain\Tenancy\Enums\AssetCategory;
use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Asset>
 */
class AssetFactory extends Factory
{
    protected $model = Asset::class;

    /**
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'tenant_id' => Tenant::factory(),
            'name' => fake()->words(3, true),
            'asset_code' => 'AST-'.fake()->unique()->numerify('###'),
            'category' => AssetCategory::Laptop,
            'serial_number' => strtoupper(fake()->bothify('SN-####??')),
            'purchase_date' => fake()->optional()->date(),
            'purchase_cost' => fake()->optional()->randomFloat(2, 100, 5000),
            'status' => AssetStatus::Available,
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
