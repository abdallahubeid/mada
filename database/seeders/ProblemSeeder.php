<?php

namespace Database\Seeders;

use App\Models\Problem;
use Illuminate\Database\Seeder;

/**
 * Seeds the landing-page Problems / Challenges cards (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class ProblemSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            /*
             * "المشاريع" removed from the first card on 2026-08-10 — the module
             * does not exist (see ModuleSeeder). The pain points below are
             * deliberately concrete rather than generic: each one names a
             * failure a finance or HR director has actually lived through, and
             * each maps to a capability that is genuinely built.
             */
            [
                'title' => 'أنظمة متفرقة لا تتحدث معًا',
                'description' => 'ملف الموظف في نظام، وعقده في ملف، وحضوره في جدول ثالث. كل رقم يُعاد إدخاله يدوياً هو خطأ ينتظر أن يظهر في مسيّرة الرواتب.',
                'icon_key' => 'ph:link-bold',
            ],
            [
                'title' => 'عمليات يدوية تستنزف الفرق',
                'description' => 'طلب إجازة ينتظر رداً في بريد، ومصروف يُطارَد في مجموعة محادثة. القرار يتأخر، والمسؤولية تضيع بين الأطراف.',
                'icon_key' => 'ph:clock-bold',
            ],
            [
                'title' => 'غياب الرؤية المالية اللحظية',
                'description' => 'تكلفة الرواتب والمصروفات لا تتضح إلا بعد إقفال الشهر — أي بعد أن يكون وقت التصحيح قد فات.',
                'icon_key' => 'ph:chart-bar-bold',
            ],
            [
                'title' => 'مخاوف أمنية على البيانات',
                'description' => 'من اطّلع على الرواتب؟ ومن عدّل العقد؟ بلا صلاحيات دقيقة وسجل تدقيق، لا توجد إجابة تصلح لتقديمها في مراجعة.',
                'icon_key' => 'ph:warning-bold',
            ],
        ];

        foreach ($items as $index => $item) {
            Problem::query()->updateOrCreate(
                ['title' => $item['title']],
                [
                    'description' => $item['description'],
                    'icon_key' => $item['icon_key'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
