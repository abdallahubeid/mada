<?php

namespace Database\Seeders;

use App\Models\Module;
use Illuminate\Database\Seeder;

/**
 * Seeds landing-page module cards (Modules grid + Solutions sidebar subset).
 */
class ModuleSeeder extends Seeder
{
    public function run(): void
    {
        $items = [
            [
                'title' => 'الموارد البشرية',
                'description' => 'الموظفون والأقسام والعقود، الحضور والإجازات، الأصول والتقييمات.',
                'icon' => 'ph:users-three-bold',
            ],
            /*
             * "الفوترة" removed on 2026-08-10 — client invoicing is Phase 2B and
             * blocked (ADR-18). `tenant_invoices` is Veyra billing the tenant,
             * not the tenant billing its own customers.
             */
            [
                'title' => 'المالية والرواتب',
                'description' => 'مسيّرات الرواتب، قسائم مقفلة بعد الاعتماد، المصروفات، وتسويات نهاية الخدمة.',
                'icon' => 'ph:credit-card-bold',
            ],
            /*
             * Replaced the former "المشاريع والعمليات (كانبان، المهام، تسجيل
             * الوقت)" card on 2026-08-09: none of it exists. There is no
             * `projects`, `timesheets` or `task_statuses` table, and `tasks` is
             * an HR line-manager assignment tool with no project dimension
             * (DEVELOPMENT_ROADMAP.md, "Phase 1 Carry-Over Debt"). Recruitment
             * is a real, shipped module and takes the slot.
             */
            [
                'title' => 'التوظيف والمقابلات',
                'description' => 'بوابة وظائف عامة، إدارة المتقدمين، وجدولة مقابلات برسائل جاهزة.',
                'icon' => 'ph:identification-badge-bold',
            ],
            [
                'title' => 'الدعم والتذاكر',
                'description' => 'محادثات دعم لكل مؤسسة مع تتبّع الحالة حتى الإغلاق.',
                'icon' => 'ph:chat-teardrop-dots-bold',
            ],
            [
                'title' => 'إدارة المستأجرين',
                'description' => 'اعتماد ورفض وإيقاف وإعادة تفعيل عبر دورة حياة من ست مراحل.',
                'icon' => 'ph:buildings-bold',
            ],
            [
                'title' => 'الأمان والصلاحيات',
                'description' => 'أدوار مخصّصة لكل مؤسسة، تحقق بخطوتين للمشرفين، وسجل تدقيق كامل.',
                'icon' => 'ph:shield-check-bold',
            ],
        ];

        /*
         * Retire the card the recruitment module replaced on 2026-08-09.
         *
         * That change swapped the title, and updateOrCreate() matches on
         * `title` — so on an already-seeded database it created a NEW row and
         * left «المشاريع والعمليات» published. The module has never existed
         * (no `projects`, `timesheets` or `task_statuses` table), which means
         * the landing page went on advertising it for the entire period it was
         * believed to have been removed. Retiring it explicitly is the only
         * thing that actually takes it off the page.
         */
        Module::query()->where('title', 'المشاريع والعمليات')->delete();

        foreach ($items as $index => $item) {
            Module::query()->updateOrCreate(
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
