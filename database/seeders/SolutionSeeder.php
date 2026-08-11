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
            /*
             * "المشاريع" dropped — the module does not exist (see ModuleSeeder).
             * Each bullet names a mechanism that exists in code rather than a
             * benefit adjective: the approval separation is BR-603, the work
             * ledger is ADR-21, the isolation is ADR-02.
             */
            [
                'title' => 'دورة حياة الموظف كاملة في مسار واحد: من إعلان الوظيفة إلى المقابلة إلى العقد إلى مستحق نهاية الخدمة.',
                'icon' => 'ph:check-bold',
            ],
            [
                'title' => 'محرّك موافقات موحّد للإجازات والمصروفات ومسيّرات الرواتب، بفصل صارم بين مَن يُعدّ المعاملة ومَن يعتمدها.',
                'icon' => 'ph:check-bold',
            ],
            [
                'title' => 'سجل عمل موحّد يوفّق الحضور والإجازات آلياً، فتُحتسب خصومات الغياب من واقع مُثبت لا من تقدير يدوي.',
                'icon' => 'ph:check-bold',
            ],
            [
                'title' => 'عزل بيانات لكل مؤسسة على مستوى الصف، وسجل تدقيق يجيب عن «من فعل ماذا ومتى» في أي لحظة.',
                'icon' => 'ph:check-bold',
            ],
        ];

        /*
         * Retire every superseded bullet. These accumulated across two
         * rewrites: updateOrCreate() keys on `title`, so each reword added a
         * row instead of replacing one, and the section was rendering NINE
         * bullets — including the original that still named المشاريع, a module
         * that does not exist.
         */
        Solution::query()->whereIn('title', [
            'نظام واحد موحّد يربط الموارد البشرية والمشاريع والرواتب والمالية.',
            'نظام واحد موحّد يربط الموارد البشرية والتوظيف والرواتب والمالية.',
            'أتمتة كاملة للموافقات وسير العمل بدل العمليات اليدوية.',
            'لوحة تحكم مالية لحظية تمنحك رؤية فورية على أداء مؤسستك.',
            'عزل صارم للبيانات وأمان على مستوى المؤسسة مع سجل نشاط كامل.',
        ])->delete();

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
