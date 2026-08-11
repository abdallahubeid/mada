<?php

namespace Database\Seeders;

use App\Models\Plan;
use Illuminate\Database\Seeder;

/**
 * Seeds SaaS pricing plans for the landing page (docs/LANDING_CMS_IMPLEMENTATION.md).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * LAUNCH PRICING — EVERY PLAN IS FREE RIGHT NOW
 *
 * The monthly/yearly figures are retained as the published list price so the
 * tiers still communicate relative value, but no plan is billed during the
 * current period. The free status is stated in three places a reader cannot
 * miss: the tier's tagline, its first feature bullet, and the section heading
 * seeded by {@see SettingSeeder}.
 *
 * Plan limits are REFERENCE VALUES, not enforced ones: `CheckFeatureLimit` is
 * Phase 4 scope (DEVELOPMENT_ROADMAP.md) and no code currently blocks a tenant
 * at these numbers. They are seeded because the Super Admin Plans screen reads
 * them, and they are worded here as inclusions rather than as caps.
 *
 * Every bullet below names a capability that EXISTS in this codebase. Claims
 * removed in the 2026-08-09 content pass, because nothing implements them:
 * "تكامل الذكاء الاصطناعي" (no AI anywhere in the app) and
 * "استضافة خاصة (On-Premise)" (no such deployment path).
 * ─────────────────────────────────────────────────────────────────────────
 */
class PlanSeeder extends Seeder
{
    /** Repeated verbatim as the first bullet of every tier. */
    private const FREE_BULLET = 'مجاني بالكامل خلال الفترة الحالية — بجميع المزايا ودون رسوم';

    public function run(): void
    {
        $items = [
            [
                'slug' => 'startup',
                'name' => 'الأساسية',
                'tagline' => 'للشركات الناشئة والفرق الصغيرة — مجانية بالكامل حالياً',
                'price_monthly' => 49,
                'price_yearly' => 39,
                'cta_label' => 'ابدأ مجاناً الآن',
                'cta_url' => '/register',
                'is_highlighted' => false,
                'limits' => [
                    'max_employees' => '25',
                    'max_departments' => '5',
                    'max_storage_mb' => '1024',
                ],
                'features' => [
                    self::FREE_BULLET,
                    'ملفات الموظفين والعقود والأقسام ودورة حياة التوظيف',
                    'الحضور والانصراف والإجازات عبر سجل عمل مُوحّد',
                    'أدوار وصلاحيات جاهزة مع سلة محذوفات قابلة للاستعادة',
                ],
            ],
            [
                'slug' => 'growth',
                'name' => 'النمو',
                'tagline' => 'للمؤسسات المتوسطة سريعة النمو — مجانية بالكامل حالياً',
                'price_monthly' => 129,
                'price_yearly' => 99,
                'cta_label' => 'ابدأ مجاناً الآن',
                'cta_url' => '/register',
                'is_highlighted' => true,
                'limits' => [
                    'max_employees' => '100',
                    'max_departments' => '20',
                    'max_storage_mb' => '10240',
                ],
                'features' => [
                    self::FREE_BULLET,
                    'كل ما في الأساسية، مع سعة أكبر لفرق أوسع',
                    'مسيّرات رواتب باعتماد مزدوج وقسائم رواتب للموظفين',
                    'التوظيف الكامل: وظائف، متقدمون، وجدولة مقابلات ببريد مخصّص',
                    'مطالبات المصروفات وتسويات نهاية الخدمة بقواعد قابلة للضبط',
                    'إشعارات لحظية ولوحة تحكم مالية للتكاليف',
                ],
            ],
            [
                'slug' => 'enterprise',
                'name' => 'Enterprise',
                'tagline' => 'للمؤسسات الكبيرة ومتطلباتها الخاصة — مجانية بالكامل حالياً',
                'price_monthly' => null,
                'price_yearly' => null,
                'cta_label' => 'تواصل مع المبيعات',
                'cta_url' => '/contact',
                'is_highlighted' => false,
                'limits' => [
                    'max_employees' => 'unlimited',
                    'max_departments' => 'unlimited',
                    'max_storage_mb' => 'unlimited',
                ],
                'features' => [
                    self::FREE_BULLET,
                    'عدد غير محدود من المستخدمين والأقسام',
                    'عزل بيانات على مستوى الصف لكل مؤسسة مع أدوار مخصّصة',
                    'سجل تدقيق شامل يرصد كل عملية حسّاسة',
                    'بوابة عامة لكل مؤسسة: صفحة تعريفية، وظائف، ونموذج تواصل',
                ],
            ],
        ];

        foreach ($items as $index => $item) {
            $features = $item['features'];
            $limits = $item['limits'];
            unset($item['features'], $item['limits']);

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

            $sort = 0;

            foreach ($limits as $key => $value) {
                $plan->features()->create([
                    'label' => $key,
                    'feature_key' => $key,
                    'value' => $value,
                    'sort_order' => $sort++,
                ]);
            }

            foreach ($features as $label) {
                $plan->features()->create([
                    'label' => $label,
                    'sort_order' => $sort++,
                ]);
            }
        }
    }
}
