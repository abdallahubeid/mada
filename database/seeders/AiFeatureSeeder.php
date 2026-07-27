<?php

namespace Database\Seeders;

use App\Models\AiFeature;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page AI roadmap cards (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class AiFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'مساعد ذكي للموارد البشرية',
                'description' => 'إجابات فورية عن سياسات الإجازات والرواتب لموظفيك.',
                'icon' => 'ph:sparkle-bold',
            ],
            [
                'title' => 'رؤى مالية تنبؤية',
                'description' => 'توقعات ذكية للتدفق النقدي واكتشاف الأنماط غير المعتادة.',
                'icon' => 'ph:sparkle-bold',
            ],
            [
                'title' => 'أتمتة سير العمل',
                'description' => 'اقتراح وتنفيذ خطوات الموافقة تلقائياً حسب سياق كل طلب.',
                'icon' => 'ph:sparkle-bold',
            ],
        ];

        foreach ($items as $index => $item) {
            AiFeature::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'description' => $item['description'],
                    'icon' => $item['icon'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
