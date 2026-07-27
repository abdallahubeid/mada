<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds SaaS pricing plans for the landing page (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class PlanSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'slug' => 'startup',
                'name' => 'الأساسية',
                'tagline' => 'للشركات الناشئة والمؤسسات الصغيرة',
                'price_monthly' => 49,
                'price_yearly' => 39,
                'cta_label' => 'ابدأ الآن',
                'cta_url' => '/register',
                'is_highlighted' => false,
                'features' => [
                    'حتى 10 مستخدمين',
                    'إدارة الموارد البشرية والرواتب',
                    'دعم عبر البريد الإلكتروني',
                    'تقارير أساسية',
                ],
            ],
            [
                'slug' => 'growth',
                'name' => 'النمو',
                'tagline' => 'للمؤسسات المتوسطة سريعة النمو',
                'price_monthly' => 129,
                'price_yearly' => 99,
                'cta_label' => 'ابدأ الآن',
                'cta_url' => '/register',
                'is_highlighted' => true,
                'features' => [
                    'حتى 100 مستخدم',
                    'الرواتب والتوظيف الكاملة',
                    'دعم أولوية على مدار الساعة',
                    'تقارير وتحليلات متقدمة',
                    'صلاحيات وأدوار مخصصة',
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'tagline' => 'للمؤسسات الكبيرة ومتطلبات مخصصة',
                'price_monthly' => null,
                'price_yearly' => null,
                'cta_label' => 'تواصل مع المبيعات',
                'cta_url' => '/contact',
                'is_highlighted' => false,
                'features' => [
                    'مستخدمين غير محدودين',
                    'تكامل الذكاء الاصطناعي',
                    'مدير حساب مخصص',
                    'استضافة خاصة (On-Premise)',
                ],
            ],
        ];

        foreach ($items as $index => $item) {
            $features = $item['features'];
            unset($item['features']);

            $plan = Plan::query()->withTrashed()->updateOrCreate(
                ['slug' => $item['slug']],
                [
                    ...$item,
                    'currency' => 'USD',
                    'is_active' => true,
                    'sort_order' => $index,
                ],
            );

            if ($plan->trashed()) {
                $plan->restore();
            }

            $plan->features()->delete();

            foreach ($features as $featureIndex => $label) {
                $plan->features()->create([
                    'label' => $label,
                    'sort_order' => $featureIndex,
                ]);
            }
        }
    }
}
