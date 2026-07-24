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
        ];

        foreach ($settings as $key => $value) {
            Setting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => $value],
            );
        }
    }
}
