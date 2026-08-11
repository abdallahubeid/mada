<?php

namespace Database\Seeders;

use App\Models\AiFeature;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page AI roadmap cards (docs/LANDING_CMS_IMPLEMENTATION.md).
 */
class AiFeatureSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            /*
             * The product roadmap section, badged «قريباً · خارطة الطريق».
             *
             * The accounting card was added here on 2026-08-10 rather than to
             * the finance offering: there is no `accounts`, `journal_entries`
             * or financial-statement table anywhere in the codebase, so listing
             * it as a shipped capability would be exactly the kind of claim
             * that fails a due-diligence review. Stating it as roadmap is both
             * honest and a stronger sales position than silence — a buyer
             * evaluating an ERP asks about the ledger either way.
             *
             * "أتمتة سير العمل" was dropped: the approval engine it described
             * is already built and shipped, so advertising it as forthcoming
             * understated the product.
             */
            [
                'title' => 'الدفتر المحاسبي والقوائم المالية',
                'description' => 'شجرة حسابات وقيود يومية وقوائم مالية تُبنى فوق بيانات الرواتب والمصروفات القائمة. غير متاحة اليوم.',
                'icon' => 'ph:sparkle-bold',
            ],
            [
                'title' => 'مساعد ذكي للموارد البشرية',
                'description' => 'إجابات فورية لموظفيك عن سياسات الإجازات والرواتب، مستندة إلى بيانات مؤسستك.',
                'icon' => 'ph:sparkle-bold',
            ],
            [
                'title' => 'رؤى مالية تنبؤية',
                'description' => 'توقعات للتكاليف القادمة واكتشاف الأنماط غير المعتادة قبل أن تتحول إلى مفاجأة.',
                'icon' => 'ph:sparkle-bold',
            ],
        ];

        // Retire the card this replaced — see the note in FeatureSeeder on why
        // a rename leaves the original row published.
        AiFeature::query()->where('title', 'أتمتة سير العمل')->delete();

        foreach ($items as $index => $item) {
            AiFeature::query()->updateOrCreate(
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
