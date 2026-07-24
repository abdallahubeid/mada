<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;
use Illuminate\Support\Str;

/**
 * Seeds SaaS plans from config/plans.php (docs/MARKETING_CMS.md).
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $currency = (string) config('plans.currency', '$');
        $currencyCode = $currency === '$' ? 'USD' : 'USD';

        foreach (array_values(config('plans.tiers', [])) as $index => $tier) {
            $slug = Str::slug($tier['name']);

            $plan = Plan::query()->updateOrCreate(
                ['slug' => $slug],
                [
                    'name' => $tier['name'],
                    'tagline' => $tier['tagline'] ?? null,
                    'price_monthly' => $tier['monthly'],
                    'price_yearly' => $tier['yearly'],
                    'currency' => $currencyCode,
                    'cta_label' => $tier['cta'],
                    'cta_url' => $tier['href'],
                    'is_highlighted' => (bool) ($tier['highlighted'] ?? false),
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );

            $plan->features()->delete();

            foreach (array_values($tier['features'] ?? []) as $featureIndex => $label) {
                $plan->features()->create([
                    'label' => $label,
                    'sort_order' => $featureIndex,
                ]);
            }
        }
    }
}
