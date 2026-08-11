<?php

namespace Database\Seeders;

use App\Models\Feature;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page Why Us / differentiators cards (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class FeatureSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            /*
             * Rewritten 2026-08-10. Three of the four previous cards were
             * generic or unverifiable:
             *
             *  - "عربي أولاً" was true but read as a translation checkbox rather
             *    than an engineering commitment; it now names the mechanism.
             *  - "إعداد سريع" advertised "أدوات استيراد" — there is no import
             *    feature. The guided wizard and auto-seeded roles are real.
             *  - "دعم يتحدث لغتك" promised 24/7 support on higher plans, which
             *    is an operational commitment nothing in the product backs.
             *    Replaced with auditability, which is code-backed and is the
             *    question an enterprise buyer actually asks.
             *
             * "تشفير للأسرار الحساسة" was already removed on 2026-08-09: there
             * is not a single `encrypted` cast, and `platform_settings` — the
             * table ADR-16 designated for encrypted secrets — was dropped.
             */
            [
                'title' => 'مبني ليصمد أمام التدقيق',
                'description' => 'كل عملية حسّاسة تترك أثراً: من نفّذها ومتى وما الذي تغيّر. ومسيّرة الرواتب المعتمدة تُقفل ولا تُعدَّل — تُصحَّح بقيد تسوية مستقل يبقى ظاهراً في السجل.',
                'icon' => 'ph:file-text-bold',
            ],
            [
                'title' => 'أمان بمعايير المؤسسات',
                'description' => 'عزل بيانات كل مؤسسة على مستوى الصف يفرضه نطاق عام لا يمكن لاستعلام تجاوزه، وتحقق بخطوتين إلزامي لمشرفي المنصّة، وأدوار وصلاحيات دقيقة يضبطها كل عميل داخل مؤسسته.',
                'icon' => 'ph:shield-check-bold',
            ],
            [
                'title' => 'عربية أصيلة لا ترجمة',
                'description' => 'واجهة صُمّمت من اليمين إلى اليسار منذ أول سطر، بمسافات منطقية تنقلب مع الاتجاه ووضعين داكن وفاتح في كل مكوّن — لا قالب غربي عُرِّب لاحقاً.',
                'icon' => 'ph:translate-bold',
            ],
            [
                'title' => 'جاهز في نفس اليوم',
                'description' => 'معالج إعداد موجّه يضبط تقويم العمل والعملة والأقسام، وأدوار افتراضية تُنشأ لمؤسستك تلقائياً. ادعُ فريقك وابدأ تسجيل الحضور اليوم نفسه.',
                'icon' => 'ph:rocket-launch-bold',
            ],
        ];

        /*
         * Retire the cards these replaced.
         *
         * updateOrCreate() matches on `title`, so RENAMING a card does not
         * update it — it creates a second row and leaves the original
         * published. On an already-seeded database that silently left the old
         * copy live on the landing page, which for two of these three meant
         * claims we had just removed for being untrue ("أدوات استيراد" — no
         * import feature exists; 24/7 Arabic support — no such commitment)
         * were still being served.
         *
         * Soft-deleted rather than hard-deleted: they stay recoverable from
         * Trash, and this is idempotent — a fresh database simply matches none.
         */
        Feature::query()->whereIn('title', [
            'عربي أولاً',
            'إعداد سريع',
            'دعم يتحدث لغتك',
        ])->delete();

        foreach ($items as $index => $item) {
            Feature::query()->updateOrCreate(
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
