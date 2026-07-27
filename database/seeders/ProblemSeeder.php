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
            [
                'title' => 'أنظمة متفرقة لا تتحدث معًا',
                'description' => 'بيانات الموارد البشرية في مكان، والرواتب في آخر، والمشاريع في جداول منفصلة — ما يعني ازدواجية وأخطاء وضياع للوقت.',
                'icon_key' => 'ph:link-bold',
            ],
            [
                'title' => 'عمليات يدوية تستنزف الفرق',
                'description' => 'الموافقات والمتابعات عبر البريد والورق تبطئ اتخاذ القرار وتُنهك موظفيك في مهام متكررة بلا قيمة.',
                'icon_key' => 'ph:clock-bold',
            ],
            [
                'title' => 'غياب الرؤية المالية اللحظية',
                'description' => 'بدون لوحة تحكم موحّدة تفقد القدرة على قراءة صحة أعمالك في الوقت المناسب لاتخاذ قرارات دقيقة.',
                'icon_key' => 'ph:chart-bar-bold',
            ],
            [
                'title' => 'مخاوف أمنية على البيانات',
                'description' => 'مشاركة البيانات الحساسة عبر أدوات غير آمنة تعرّض مؤسستك لمخاطر تسريب وفقدان للثقة.',
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
