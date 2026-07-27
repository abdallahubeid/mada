<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $this->call([
            PlanSeeder::class,
            FaqSeeder::class,
            TestimonialSeeder::class,
            SettingSeeder::class,
            ProblemSeeder::class,
            SolutionSeeder::class,
            OfferingSeeder::class,
            ModuleSeeder::class,
            AiFeatureSeeder::class,
            FeatureSeeder::class,
            DemoTenantSeeder::class,
        ]);
    }
}
