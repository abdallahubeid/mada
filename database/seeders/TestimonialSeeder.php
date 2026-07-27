<?php

namespace Database\Seeders;

use App\Models\Testimonial;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page success story testimonials (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class TestimonialSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'quote' => 'وحّدنا خمسة أنظمة متفرقة في منصّة واحدة، ووفّرنا ساعات أسبوعية من العمل اليدوي. الفارق واضح منذ الشهر الأول.',
                'client_name' => 'سارة المطيري',
                'client_role' => 'مديرة الموارد البشرية',
                'organization_name' => 'مجموعة الأفق',
            ],
            [
                'quote' => 'أخيرًا نظام يفهم العربية فعلاً، الواجهة أنيقة والفريق تأقلم معها بسرعة دون تدريب طويل.',
                'client_name' => 'عبدالله الشمري',
                'client_role' => 'الرئيس التنفيذي',
                'organization_name' => 'مؤسسة نماء',
            ],
            [
                'quote' => 'مستوى الأمان وعزل البيانات كان العامل الحاسم بالنسبة لنا كجهة تتعامل مع بيانات حساسة.',
                'client_name' => 'ريم الدوسري',
                'client_role' => 'مديرة العمليات',
                'organization_name' => 'حلول بيان',
            ],
        ];

        foreach ($items as $index => $item) {
            Testimonial::query()->updateOrCreate(
                [
                    'client_name' => $item['client_name'],
                    'organization_name' => $item['organization_name'],
                ],
                [
                    'quote' => $item['quote'],
                    'client_role' => $item['client_role'],
                    'rate' => 5,
                    'sort_order' => $index,
                    'is_published' => true,
                    'tenant_id' => null,
                ],
            );
        }
    }
}
