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
                'description' => 'كل سجل يحمل معرّف مؤسسته، ونطاق عزل عام يمنع أي استعلام من تجاوز حدودها. الأدوار والصلاحيات وقنوات الإشعارات معزولة لكل عميل، ودورة حياة الحساب من ست مراحل يتحكم بها مشرف المنصّة وحده.',
                'icon' => 'ph:shield-check-bold',
            ],
            [
                'title' => 'التوظيف وإدارة الموارد البشرية',
                'description' => 'بوابة توظيف عامة باسم مؤسستك، وإدارة للمتقدمين وجدولة مقابلات برسائل جاهزة — ثم العقود والأقسام وتقويم العمل والحضور والإجازات والأصول في المسار نفسه.',
                'icon' => 'ph:users-three-bold',
            ],
            /*
             * Replaced "المشاريع والعمليات" on 2026-08-09 — Kanban, Strategic
             * Hierarchy and timesheets are all unbuilt (the first two are not
             * even in Phase 2A/2B scope). The Approval Engine and audit log are
             * real, cross-cutting, and genuinely differentiating.
             */
            [
                'title' => 'محرّك الموافقات وسجل التدقيق',
                'description' => 'مسار موافقة موحّد للإجازات والمصروفات مع تصعيد تلقائي عند التأخر، وفصل صارم بين مُعِدّ المعاملة ومُعتمِدها، وسجل تدقيق يرصد من فعل ماذا ومتى.',
                'icon' => 'ph:check-square-offset-bold',
            ],
            /*
             * "فوترة" dropped: client invoicing is Phase 2B and blocked on the
             * unbuilt Projects & Timesheets module (ADR-18). The finance
             * dashboard is deliberately cost-side only, so the copy says so.
             */
            /*
             * Scope note, 2026-08-10: this card covers payroll, expenses and
             * end-of-service only. There is no chart of accounts, no journal
             * entries and no financial statements in the codebase, so none are
             * claimed here — they sit in the roadmap section instead. Client
             * invoicing likewise stays out: it is Phase 2B and blocked on the
             * unbuilt Projects & Timesheets module (ADR-18), which is also why
             * the dashboard is cost-side only and the copy says so.
             */
            [
                'title' => 'الرواتب والمصروفات',
                'description' => 'مسيّرات رواتب باعتماد مزدوج تُقفل نهائياً بعد الاعتماد ولا تقبل تعديلاً — أي تصحيح يُسجَّل كقيد تسوية مستقل يبقى ظاهراً. ومعها مطالبات المصروفات، وتسويات نهاية الخدمة بقواعد تضبطها كل مؤسسة وتُحفَظ نسخة منها مع كل تسوية، ولوحة تحكم للتكاليف.',
                'icon' => 'ph:credit-card-bold',
            ],
        ];

        // Same rename-orphan problem as ModuleSeeder: both of these were
        // superseded by title changes and stayed published. The first names a
        // module that does not exist; the second promised "التحليلات المالية"
        // beyond the cost-side dashboard that is actually built.
        Offering::query()->whereIn('title', [
            'المشاريع والعمليات',
            'الرواتب والتحليلات المالية',
        ])->delete();

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
