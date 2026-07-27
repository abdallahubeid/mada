<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page Why Us / differentiators cards (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'عربي أولاً',
                'description' => 'واجهة مصممة أصلاً للعربية بدعم كامل للاتجاه من اليمين لليسار، لا مجرد ترجمة.',
                'icon' => 'ph:translate-bold',
            ],
            [
                'title' => 'أمان بمعايير المؤسسات',
                'description' => 'عزل صارم للبيانات، تحقق بخطوتين إلزامي، وتشفير للأسرار الحساسة.',
                'icon' => 'ph:shield-check-bold',
            ],
            [
                'title' => 'إعداد سريع',
                'description' => 'فعّل مؤسستك وابدأ العمل في نفس اليوم مع تدفق إعداد موجّه وأدوات استيراد.',
                'icon' => 'ph:arrow-down-bold',
            ],
            [
                'title' => 'دعم يتحدث لغتك',
                'description' => 'فريق دعم عربي متجاوب على مدار الساعة للخطط الأعلى، وقاعدة معرفية شاملة.',
                'icon' => 'ph:chat-dots-bold',
            ],
        ];

        foreach ($items as $index => $item) {
            Feature::query()->updateOrCreate(
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
