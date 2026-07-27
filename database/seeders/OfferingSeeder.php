<?php

namespace Database\Seeders;

use App\Models\Offering;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page Offerings section cards (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class OfferingSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'أمان متعدد المستأجرين',
                'description' => 'عزل كامل لبيانات كل مؤسسة على مستوى الصفوف، مع سياسات وصول ودورة حياة حساب من 5 مراحل لكل عميل.',
                'icon' => 'ph:shield-check-bold',
            ],
            [
                'title' => 'التوظيف وإدارة الموارد البشرية',
                'description' => 'من نشر الوظائف واستقبال المتقدمين إلى العقود والحضور — دورة حياة الموظف كاملة في نظام واحد.',
                'icon' => 'ph:users-three-bold',
            ],
            [
                'title' => 'المشاريع والعمليات',
                'description' => 'لوحات كانبان، هيكلية استراتيجية، وتسجيل ساعات العمل — لإدارة تنفيذية شاملة لكل مشاريع مؤسستك.',
                'icon' => 'ph:kanban-bold',
            ],
            [
                'title' => 'الرواتب والتحليلات المالية',
                'description' => 'معالجة رواتب دقيقة، فوترة ومصاريف، ولوحة تحكم مالية تنفيذية تمنحك رؤية فورية على صحة أعمالك.',
                'icon' => 'ph:credit-card-bold',
            ],
        ];

        foreach ($items as $index => $item) {
            Offering::query()->updateOrCreate(
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
