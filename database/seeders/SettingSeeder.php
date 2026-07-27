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
            'hero_badge_text' => 'منصة SaaS متكاملة لإدارة المؤسسات',
            'hero_title' => 'مستقبل إدارة المؤسسات بذكاء وفخامة',
            'hero_description' => 'منصة Veyra ERP الشاملة لإدارة الموارد البشرية، المشاريع، والرواتب — أتمتة كاملة لعمليات مؤسستك في نظام واحد أنيق وذكي، بدقة تنظيمية وأمان تام لبياناتك.',
            'hero_btn1_text' => 'ابدأ التجربة المجانية',
            'hero_btn1_link' => '#',
            'hero_btn2_text' => 'احجز عرضًا توضيحيًا',
            'hero_btn2_link' => '#',
            'problems_badge_text' => 'التحديات',
            'problems_title' => 'هل تبدو هذه المشاكل مألوفة؟',
            'problems_sub_title' => 'معظم المؤسسات تُدار عبر أدوات متفرقة تخلق فوضى تشغيلية بدل أن تحلّها.',
            'solutions_badge_text' => 'الحل',
            'solutions_title' => 'منصّة واحدة تدير كل شيء بسلاسة',
            'solutions_sub_title' => 'يوحّد Veyra ERP كل عمليات مؤسستك في نظام واحد متكامل، فتختفي الفوضى ويحلّ محلّها الوضوح.',
            'solutions_btn_text' => 'اكتشف كل المميزات',
            'solutions_btn_link' => '#modules',
            'offerings_title' => 'قوة تتناسب مع طموحاتك',
            'offerings_sub_title' => 'كل ما تحتاجه مؤسستك من أدوات إدارية وتشغيلية في نظام واحد متكامل.',
            'modules_badge_text' => 'الوحدات',
            'modules_title' => 'وحدات متكاملة لكل احتياجات مؤسستك',
            'modules_sub_title' => 'كل وحدة مصممة لتعمل بتناغم مع البقية، فتنساب البيانات بينها دون جهد.',
            'product_previews_badge_text' => 'جولة في المنتج',
            'product_previews_title' => 'واجهة أنيقة تجعل العمل متعة',
            'product_previews_sub_title' => 'تصميم عصري يركّز على الوضوح والسرعة، بدعم كامل للعربية والوضعين الفاتح والداكن.',
            'ai_badge_text' => 'قريباً · خارطة الطريق',
            'ai_title' => 'ذكاء اصطناعي يعمل لصالحك',
            'ai_sub_title' => 'قدرات ذكية قيد التطوير ضمن خارطة طريق Veyra — نشاركك رؤيتنا القادمة بشفافية.',
            'why_us_badge_text' => 'لماذا Veyra',
            'why_us_title' => 'ما الذي يميّزنا عن غيرنا',
            'why_us_sub_title' => 'لم نبنِ مجرد أداة أخرى، بل منصّة تفهم طبيعة المؤسسات في منطقتنا.',
            'testimonials_badge_text' => 'قصص نجاح',
            'testimonials_title' => 'مؤسسات تنمو مع Veyra',
            'testimonials_sub_title' => 'لا تأخذ كلامنا فقط — استمع لمن اختبروا الفرق بأنفسهم.',
            'pricing_title' => 'استثمار ذكي لنمو مستدام',
            'pricing_sub_title' => 'اختر الخطة التي تناسب حجم مؤسستك، وطوّرها متى شئت.',
            'pricing_btn_text' => 'قارن جميع المزايا بالتفصيل',
            'pricing_btn_link' => '/pricing',
            'faq_title' => 'الأسئلة الشائعة',
            'faq_sub_title' => 'إجابات سريعة عن أكثر ما يسأل عنه عملاؤنا.',
            'cta_title' => 'جاهز لتحويل مؤسستك؟',
            'cta_sub_title' => 'ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق.',
            'cta_btn1_text' => 'ابدأ التجربة المجانية',
            'cta_btn1_link' => '/register',
            'cta_btn2_text' => 'تواصل مع المبيعات',
            'cta_btn2_link' => '/contact',
            'footer_description' => 'نظام إدارة الموارد المؤسسي الذكي الذي يواكب طموحات مؤسستك القادمة.',
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
