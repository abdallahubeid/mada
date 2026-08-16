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
            /*
             * The `**...**` in `hero_title` is not markdown — the hero component
             * splits on it to wrap that phrase in the hand-drawn marker
             * highlight. A title without the delimiter renders unmarked and
             * unbroken, so CMS-edited titles never break the layout.
             *
             * Keep the marked phrase SHORT (two or three words). The swash is
             * drawn to the phrase width, and a marked half-sentence stops
             * reading as emphasis and starts reading as a highlighter accident.
             */
            'hero_badge_text' => 'مجاني بالكامل خلال فترة الإطلاق',
            'hero_title' => 'كل ما تحتاجه لإدارة ((فريقك))، **في مكان واحد**',
            'hero_description' => 'التوظيف، العقود، الحضور، الإجازات، والرواتب — من نفس الشاشة. بدون جداول جانبية، وبدون أنظمة متفرقة لا يتحدث بعضها مع بعض.',
            'hero_btn1_text' => 'ابدأ الآن — مجاناً',
            'hero_btn1_link' => '/register',
            'hero_btn2_text' => 'تواصل مع مستشار',
            'hero_btn2_link' => '/contact',
            'problems_badge_text' => 'التحديات',
            'problems_title' => 'أين تتسرّب كفاءة مؤسستك اليوم؟',
            'problems_sub_title' => 'المشكلة نادراً ما تكون في الأدوات نفسها، بل في المسافة بينها — وهذه هي الفجوات التي تكلّف وقتاً ومالاً وثقة.',
            'solutions_badge_text' => 'الحل',
            'solutions_title' => 'مصدر واحد للحقيقة عبر **مؤسستك بالكامل**',
            'solutions_sub_title' => 'تنتقل البيانات من إعلان الوظيفة إلى العقد إلى الحضور إلى مسيّرة الرواتب دون إعادة إدخال واحدة، ودون جدول جانبي واحد.',
            'solutions_btn_text' => 'اكتشف كل المميزات',
            'solutions_btn_link' => '#modules',
            'offerings_title' => 'أربع ركائز يقوم عليها النظام',
            'offerings_sub_title' => 'كل ركيزة مبنية ومُفعّلة اليوم — ما هو قيد التطوير معروض بوضوح في خارطة الطريق أدناه.',
            'modules_badge_text' => 'الوحدات',
            'modules_title' => 'وحدات تعمل **معاً**، لا بجوار بعضها',
            'modules_sub_title' => 'كل وحدة تكتب وتقرأ من البيانات المعزولة نفسها، فلا يوجد رقمان لنفس الحقيقة ولا مطابقة يدوية في نهاية الشهر.',
            'product_previews_badge_text' => 'جولة في المنتج',
            'product_previews_title' => 'شاهد النظام **قبل أن تسجّل**',
            'product_previews_sub_title' => 'واجهة عربية بالكامل، مبنية من اليمين لليسار — لا ترجمة مقلوبة ولا شاشات نصفها إنجليزي.',
            /*
             * Video section. `video_url` is blank by default so a fresh install
             * falls back to `previews_video`; with neither set the band renders
             * nothing at all rather than an empty frame.
             */
            'is_video_section_active' => '1',
            'video_title' => 'شاهد مدى أثناء التشغيل',
            'video_description' => 'جولة قصيرة داخل النظام — من إضافة موظف حتى اعتماد مسيرة الرواتب.',
            'video_url' => '',
            'previews_video' => 'media/mada-product-tour.mp4',
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
            'why_us_badge_text' => 'لماذا مدى',
            'why_us_title' => 'ما الذي **يميّزنا** عن غيرنا',
            'why_us_sub_title' => 'أربعة قرارات هندسية تفصل بين نظام يصمد أمام المدقّق وآخر يبدو جميلاً في العرض التقديمي فقط.',
            'testimonials_badge_text' => 'قصص نجاح',
            'testimonials_title' => 'مؤسسات تنمو مع مدى',
            'testimonials_sub_title' => 'مؤسسات تشغّل رواتبها وحضورها على مدى كل شهر.',
            'pricing_title' => 'مجاني بالكامل خلال الفترة الحالية',
            'pricing_sub_title' => 'كل الخطط مفتوحة بجميع مزاياها دون رسوم ودون بطاقة ائتمان. اختر الحجم الذي يناسب مؤسستك اليوم، وابدأ خلال دقائق.',
            'pricing_btn_text' => 'قارن جميع المزايا بالتفصيل',
            'pricing_btn_link' => '/pricing',
            'faq_title' => 'الأسئلة الشائعة',
            'faq_sub_title' => 'أكثر ما يُسأل عنه قبل البدء.',
            'cta_title' => 'جرّبه على مؤسستك هذا الأسبوع',
            'cta_sub_title' => 'أنشئ حسابك، ادعُ فريقك، وابدأ التشغيل. لا رسوم ولا بطاقة بنكية.',
            'cta_btn1_text' => 'ابدأ الآن — مجاناً',
            'cta_btn1_link' => '/register',
            'cta_btn2_text' => 'تحدّث مع مستشار',
            'cta_btn2_link' => '/contact',
            'footer_description' => 'نظام واحد يدير التوظيف والموارد البشرية والحضور والرواتب. بيانات كل مؤسسة معزولة، وكل عملية حسّاسة مسجّلة.',
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
