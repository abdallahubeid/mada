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
                'description' => 'الموظفون، العقود، الحضور، والإجازات.',
                'icon' => 'ph:users-three-bold',
            ],
            [
                'title' => 'المالية والرواتب',
                'description' => 'مسير الرواتب، الفوترة، والمصاريف.',
                'icon' => 'ph:credit-card-bold',
            ],
            [
                'title' => 'المشاريع والعمليات',
                'description' => 'كانبان، المهام، وتسجيل الوقت.',
                'icon' => 'ph:kanban-bold',
            ],
            [
                'title' => 'الدعم والتذاكر',
                'description' => 'محادثات الدعم وإدارة الاستفسارات.',
                'icon' => 'ph:chat-teardrop-dots-bold',
            ],
            [
                'title' => 'إدارة المستأجرين',
                'description' => 'عزل وإدارة دورة حياة كل مؤسسة.',
                'icon' => 'ph:buildings-bold',
            ],
            [
                'title' => 'الأمان والصلاحيات',
                'description' => 'أدوار دقيقة، 2FA، وسجل نشاط كامل.',
                'icon' => 'ph:shield-check-bold',
            ],
        ];

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
