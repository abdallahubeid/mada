<?php

namespace Database\Seeders;

use App\Models\Setting;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page CMS settings (docs/MARKETING_CMS.md).
 */
class SettingSeeder extends Seeder
{
    public function run(): void
    {
        $settings = [
            'hero_badge_text' => 'منصّة ERP مؤسسية متعددة المستأجرين',
            'hero_title' => 'من أول إعلان وظيفة إلى آخر مستحق نهاية خدمة — في نظام واحد',
            'hero_description' => 'يدير Veyra دورة حياة الموظف كاملة: التوظيف والمقابلات، العقود والأقسام، الحضور والإجازات، مسيّرات رواتب باعتماد مزدوج تُقفل نهائياً بعد الاعتماد، المصروفات، وتسويات نهاية خدمة بقواعد تضبطها كل مؤسسة بنفسها. بيانات كل عميل معزولة على مستوى الصف، وكل عملية حسّاسة تترك أثراً في سجل التدقيق. مفتوح بجميع مزاياه ومجاناً خلال الفترة الحالية.',
            'hero_btn1_text' => 'ابدأ مجاناً الآن',
            'hero_btn1_link' => '/register',
            'hero_btn2_text' => 'تصفّح الوحدات',
            'hero_btn2_link' => '#modules',
            'problems_badge_text' => 'التحديات',
            'problems_title' => 'أين تتسرّب كفاءة مؤسستك اليوم؟',
            'problems_sub_title' => 'المشكلة نادراً ما تكون في الأدوات نفسها، بل في المسافة بينها — وهذه هي الفجوات التي تكلّف وقتاً ومالاً وثقة.',
            'solutions_badge_text' => 'الحل',
            'solutions_title' => 'مصدر واحد للحقيقة عبر مؤسستك بالكامل',
            'solutions_sub_title' => 'تنتقل البيانات من إعلان الوظيفة إلى العقد إلى الحضور إلى مسيّرة الرواتب دون إعادة إدخال واحدة، ودون جدول جانبي واحد.',
            'solutions_btn_text' => 'اكتشف كل المميزات',
            'solutions_btn_link' => '#modules',
            'offerings_title' => 'أربع ركائز يقوم عليها النظام',
            'offerings_sub_title' => 'كل ركيزة مبنية ومُفعّلة اليوم — ما هو قيد التطوير معروض بوضوح في خارطة الطريق أدناه.',
            'modules_badge_text' => 'الوحدات',
            'modules_title' => 'وحدات تعمل معاً، لا بجوار بعضها',
            'modules_sub_title' => 'كل وحدة تكتب وتقرأ من البيانات المعزولة نفسها، فلا يوجد رقمان لنفس الحقيقة ولا مطابقة يدوية في نهاية الشهر.',
            'product_previews_badge_text' => 'جولة في المنتج',
            'product_previews_title' => 'واجهة أنيقة تجعل العمل متعة',
            'product_previews_sub_title' => 'تصميم عصري يركّز على الوضوح والسرعة، بدعم كامل للعربية والوضعين الفاتح والداكن.',
            /*
             * This section is the product roadmap, not an AI teaser. The keys
             * keep their `ai_*` names because the CMS screens and the settings
             * table are keyed on them; the copy was generalised on 2026-08-10
             * so the accounting ledger — which does NOT exist — has an honest,
             * visibly-tagged home instead of being implied as shipped in the
             * finance section above.
             */
            'ai_badge_text' => 'قريباً · خارطة الطريق',
            'ai_title' => 'ما نعمل عليه الآن',
            'ai_sub_title' => 'قدرات قيد التطوير ولم تُطلَق بعد. نعرضها صراحةً كي تعرف بدقة ما هو متاح اليوم وما هو قادم — دون مفاجآت بعد الاشتراك.',
            'why_us_badge_text' => 'لماذا Veyra',
            'why_us_title' => 'ما الذي يميّزنا عن غيرنا',
            'why_us_sub_title' => 'أربعة قرارات هندسية تفصل بين نظام يصمد أمام المدقّق وآخر يبدو جميلاً في العرض التقديمي فقط.',
            'testimonials_badge_text' => 'قصص نجاح',
            'testimonials_title' => 'مؤسسات تنمو مع Veyra',
            'testimonials_sub_title' => 'لا تأخذ كلامنا فقط — استمع لمن اختبروا الفرق بأنفسهم.',
            'pricing_title' => 'مجاني بالكامل خلال الفترة الحالية',
            'pricing_sub_title' => 'كل الخطط مفتوحة بجميع مزاياها دون رسوم ودون بطاقة ائتمان. اختر الحجم الذي يناسب مؤسستك اليوم، وابدأ خلال دقائق.',
            'pricing_btn_text' => 'قارن جميع المزايا بالتفصيل',
            'pricing_btn_link' => '/pricing',
            'faq_title' => 'الأسئلة الشائعة',
            'faq_sub_title' => 'إجابات سريعة عن أكثر ما يسأل عنه عملاؤنا.',
            'cta_title' => 'ابدأ اليوم — مجاناً بالكامل',
            'cta_sub_title' => 'فعّل مؤسستك خلال دقائق: أنشئ حسابك، وادعُ فريقك، وابدأ التشغيل. دون رسوم ودون بطاقة ائتمان خلال الفترة الحالية.',
            'cta_btn1_text' => 'ابدأ مجاناً الآن',
            'cta_btn1_link' => '/register',
            'cta_btn2_text' => 'تواصل مع المبيعات',
            'cta_btn2_link' => '/contact',
            'footer_description' => 'نظام تخطيط موارد مؤسسي متعدد المستأجرين: توظيف، موارد بشرية، حضور وإجازات، رواتب ومصروفات — بعزل بيانات وسجل تدقيق لكل مؤسسة.',
            'footer_newsletter_title' => 'البريد الإلكتروني',
            'footer_newsletter_btn_text' => 'اشتراك',
            'footer_title1' => 'المنتج',
            'footer_btn1_text' => 'المميزات',
            'footer_btn1_link' => '/features',
            'footer_btn2_text' => 'الحلول',
            'footer_btn2_link' => '/solutions',
            'footer_btn3_text' => 'الأسعار',
            'footer_btn3_link' => '/pricing',
            'footer_btn4_text' => 'الأمان والامتثال',
            'footer_btn4_link' => '/security',
            'footer_title2' => 'الشركة',
            'footer_btn5_text' => 'من نحن',
            'footer_btn5_link' => '/about',
            'footer_btn6_text' => 'تواصل معنا',
            'footer_btn6_link' => '/contact',
            'footer_btn7_text' => 'الأسئلة الشائعة',
            'footer_btn7_link' => '/faq',
            'footer_title3' => 'القانونية',
            'footer_btn8_text' => 'سياسة الخصوصية',
            'footer_btn8_link' => '/privacy',
            'footer_btn9_text' => 'الشروط والأحكام',
            'footer_btn9_link' => '/terms',
            'social_btn1_text' => 'X / Twitter',
            'social_btn1_link' => 'https://twitter.com',
            'social_btn2_text' => 'LinkedIn',
            'social_btn2_link' => 'https://linkedin.com',
            'social_btn3_text' => 'Facebook',
            'social_btn3_link' => 'https://facebook.com',
            'social_btn4_text' => 'GitHub',
            'social_btn4_link' => 'https://github.com',
            'social_btn5_text' => 'YouTube',
            'social_btn5_link' => 'https://youtube.com',
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
