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
                'answer' => 'لا، صُمّمت واجهة مدى لتكون بسيطة وسهلة الاستخدام مع تدفق إعداد موجّه يمكّن فريقك من البدء خلال دقائق دون أي إعداد تقني معقد.',
            ],
            [
                'category' => 'التسعير والفوترة',
                'question' => 'هل تتوفر تجربة مجانية؟',
                'answer' => 'النظام مجاني بالكامل خلال الفترة الحالية: جميع الخطط مفتوحة بكل مزاياها دون رسوم ودون بطاقة ائتمان. الأسعار المعروضة هي أسعار القائمة المستقبلية، ولن تُفعّل الفوترة قبل إشعارك مسبقاً.',
            ],
            [
                'category' => 'الأمان',
                'question' => 'من يستطيع الاطّلاع على بيانات الرواتب داخل مؤسستي؟',
                'answer' => 'الوصول محكوم بنظام أدوار وصلاحيات دقيق يُدار من داخل مؤسستك. مسيّرات الرواتب تخضع لفصل المهام: مَن يُعدّ المسيّرة لا يستطيع اعتمادها، وبمجرد الاعتماد تُقفل نهائياً ولا تقبل أي تعديل — وأي تصحيح لاحق يُسجَّل كقيد تسوية مستقل. ويرى الموظف قسائم راتبه المعتمدة فقط.',
            ],
            [
                'category' => 'التسعير والفوترة',
                'question' => 'هل يمكنني تغيير خطتي لاحقًا؟',
                'answer' => 'بالتأكيد، يمكنك الترقية أو تغيير خطتك في أي وقت بسهولة من خلال لوحة التحكم حسب نمو احتياجات مؤسستك.',
            ],
            [
                'category' => 'الأمان',
                'question' => 'كيف يتم عزل بيانات مؤسستي عن باقي العملاء؟',
                /*
                 * Corrected on 2026-08-09. The previous answer said "عزل قواعد
                 * البيانات" (separate databases per tenant), which is not the
                 * architecture: ADR-02 is a single shared database with
                 * row-level `tenant_id` isolation enforced by a global scope.
                 * Describing it as database-level isolation would be a
                 * security claim we cannot stand behind in a due-diligence
                 * review.
                 */
                'answer' => 'يحمل كل سجل في النظام معرّف مؤسسته، ويُطبَّق نطاق عزل عام على مستوى التطبيق يمنع أي استعلام من تجاوز حدود المؤسسة. تُعزل الأدوار والصلاحيات لكل مؤسسة على حدة، كما تُعزل قنوات الإشعارات اللحظية لكل مستخدم داخل مؤسسته.',
            ],
            [
                'category' => 'الأمان',
                'question' => 'هل تدعمون التحقق بخطوتين (2FA)؟',
                /*
                 * Narrowed 2026-08-10. The previous answer said 2FA protects
                 * "حسابات المستخدمين" without qualification, which reads as
                 * every tenant user. It is implemented for platform operator
                 * accounts only (AccountSecurityController, ADR-14) — there is
                 * no tenant-user 2FA surface. Overstating a security control is
                 * the claim most likely to be checked in a procurement review.
                 */
                'answer' => 'التحقق بخطوتين مُفعّل وإلزامي على حسابات مشرفي المنصّة — وهي الحسابات الوحيدة ذات الوصول العابر للمؤسسات. أما التحقق بخطوتين لحسابات مستخدمي المؤسسات فهو ضمن خارطة الطريق ولم يُطلَق بعد؛ وحماية هذه الحسابات تقوم حالياً على الأدوار والصلاحيات الدقيقة وسجل التدقيق.',
            ],
            /*
             * Added 2026-08-10. An ERP buyer asks about the ledger during
             * evaluation whether or not the site mentions it, so the honest
             * answer is given here rather than left for a sales call to walk
             * back. Mirrors the roadmap section (AiFeatureSeeder).
             */
            [
                'category' => 'المالية',
                'question' => 'هل يشمل النظام شجرة حسابات وقوائم مالية؟',
                'answer' => 'ليس بعد. المتاح اليوم هو الجانب التشغيلي للمالية: مسيّرات الرواتب باعتماد مزدوج وقسائم تُقفل بعد الاعتماد، ومطالبات المصروفات، وتسويات نهاية الخدمة، ولوحة تحكم للتكاليف تقرأ من السجلات المعتمدة فقط. أما شجرة الحسابات والقيود اليومية والقوائم المالية فهي ضمن خارطة الطريق ولم تُطلَق — نذكر ذلك صراحةً كي تبني قرارك على ما هو متاح فعلاً.',
            ],
            [
                'category' => 'المالية',
                'question' => 'هل يحسب النظام مكافأة نهاية الخدمة تلقائياً؟',
                'answer' => 'نعم. تُحتسب التسوية النهائية آلياً من العقد وسجل الحضور والإجازات، وتشمل رصيد الإجازات غير المستخدم والراتب النسبي ومكافأة نهاية الخدمة. وقواعد الاحتساب — حدّ الشريحة، ونسب الاستحقاق، وتدرّج الاستقالة — تضبطها كل مؤسسة بنفسها وفق النظام المُطبَّق لديها، وتُحفَظ نسخة من القواعد المستخدمة مع كل تسوية، فتبقى كل تسوية معتمدة قادرة على تفسير رقمها حتى لو عُدّلت القواعد لاحقاً. القيم الافتراضية المرفقة ليست استشارة قانونية.',
            ],
            [
                'category' => 'الإعداد والدعم',
                'question' => 'كم يستغرق إعداد النظام لمؤسستي؟',
                /*
                 * "أدوات الاستيراد" removed on 2026-08-09 — there is no import
                 * feature. What does exist is the guided setup wizard and the
                 * default seeded roles (BR-102), so the answer names those.
                 */
                'answer' => 'يبدأ الإعداد بمعالج موجّه يضبط تقويم العمل والعملة والأقسام، وتُنشأ الأدوار الافتراضية لمؤسستك تلقائياً — فيصبح بإمكانك دعوة فريقك وبدء تسجيل الحضور في اليوم نفسه.',
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
