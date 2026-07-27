<?php

namespace Database\Seeders;

use App\Models\Solution;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page Solutions section bullet points (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class SolutionSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'نظام واحد موحّد يربط الموارد البشرية والمشاريع والرواتب والمالية.',
                'icon' => 'ph:check-bold',
            ],
            [
                'title' => 'أتمتة كاملة للموافقات وسير العمل بدل العمليات اليدوية.',
                'icon' => 'ph:check-bold',
            ],
            [
                'title' => 'لوحة تحكم مالية لحظية تمنحك رؤية فورية على أداء مؤسستك.',
                'icon' => 'ph:check-bold',
            ],
            [
                'title' => 'عزل صارم للبيانات وأمان على مستوى المؤسسة مع سجل نشاط كامل.',
                'icon' => 'ph:check-bold',
            ],
        ];

        foreach ($items as $index => $item) {
            Solution::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'description' => $item['title'],
                    'icon' => $item['icon'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
