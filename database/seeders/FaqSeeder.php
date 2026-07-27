<?php

namespace Database\Seeders;

use App\Models\Faq;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page FAQ items (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class FaqSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'category' => 'عام',
                'question' => 'هل أحتاج إلى خبرة تقنية لاستخدام النظام؟',
                'answer' => 'لا، صُمّمت واجهة Veyra لتكون بسيطة وسهلة الاستخدام مع تدفق إعداد موجّه يمكّن فريقك من البدء خلال دقائق دون أي إعداد تقني معقد.',
            ],
            [
                'category' => 'التسعير والفوترة',
                'question' => 'هل تتوفر تجربة مجانية؟',
                'answer' => 'نعم، نوفر فترة تجربة مجانية تتيح لك استكشاف كافة المزايا واختبار النظام مع فريقك قبل اتخاذ القرار.',
            ],
            [
                'category' => 'التسعير والفوترة',
                'question' => 'هل يمكنني تغيير خطتي لاحقًا؟',
                'answer' => 'بالتأكيد، يمكنك الترقية أو تغيير خطتك في أي وقت بسهولة من خلال لوحة التحكم حسب نمو احتياجات مؤسستك.',
            ],
            [
                'category' => 'الأمان',
                'question' => 'كيف يتم عزل بيانات مؤسستي عن باقي العملاء؟',
                'answer' => 'نعتمد بنية معمارية صارمة لعزل قواعد البيانات (Multi-tenancy isolation) لضمان أعلى مستويات الأمان والخصوصية لكل مؤسسة.',
            ],
            [
                'category' => 'الأمان',
                'question' => 'هل تدعمون التحقق بخطوتين (2FA)؟',
                'answer' => 'نعم، يدعم Veyra ميزة التحقق بخطوتين لحماية حسابات المستخدمين والوصول المصرح به فقط.',
            ],
            [
                'category' => 'الإعداد والدعم',
                'question' => 'كم يستغرق إعداد النظام لمؤسستي؟',
                'answer' => 'يمكنك تفعيل حسابك والبدء بالعمل خلال دقائق معدودة بفضل أدوات الاستيراد والتجهيز التلقائي.',
            ],
        ];

        foreach ($items as $index => $item) {
            Faq::query()->updateOrCreate(
                [
                    'category' => $item['category'],
                    'question' => $item['question'],
                ],
                [
                    'answer' => $item['answer'],
                    'sort_order' => $index,
                    'is_published' => true,
                ],
            );
        }
    }
}
