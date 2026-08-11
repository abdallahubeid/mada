<?php

namespace App\Services\Admin;

use Illuminate\Support\Facades\Route;

/**
 * Searchable index of Super Admin console pages (mirrors sidebar destinations).
 */
class AdminNavigationCatalog
{
    /**
     * @return list<array{title: string, subtitle: string, route: string, keywords: list<string>, scope: string}>
     */
    public function pages(): array
    {
        return [
            [
                'title' => 'لوحة التحكم',
                'subtitle' => 'نظرة عامة',
                'route' => 'admin.dashboard',
                'keywords' => ['لوحة', 'تحكم', 'dashboard', 'home'],
                'scope' => 'dashboard',
            ],
            [
                'title' => 'إدارة المستأجرين',
                'subtitle' => 'المستأجرون',
                'route' => 'admin.tenants',
                'keywords' => ['مستأجر', 'مستأجرين', 'tenants', 'companies'],
                'scope' => 'tenants',
            ],
            [
                'title' => 'الخطط والحدود',
                'subtitle' => 'المستأجرون',
                'route' => 'admin.plans',
                'keywords' => ['خطط', 'الخطة', 'الخُطط', 'حدود', 'plans', 'limits'],
                'scope' => 'plans',
            ],
            /*
             * Subtitles are the sidebar group each page lives under, so search
             * results read as "where would I have clicked to get here". FAQs
             * moved from المستأجرون to المحتوى when the sidebar was regrouped —
             * `faqs` rows are platform-global marketing content, not tenant
             * data (see layouts/partials/admin-sidebar.blade.php).
             */
            [
                'title' => 'الأسئلة الشائعة',
                'subtitle' => 'المحتوى',
                'route' => 'admin.faqs.index',
                'keywords' => ['أسئلة', 'شائعة', 'faq', 'faqs'],
                'scope' => 'faqs',
            ],
            [
                'title' => 'محتوى الصفحة الرئيسية',
                'subtitle' => 'المحتوى',
                'route' => 'admin.problems.index',
                'keywords' => ['محتوى', 'صفحة', 'رئيسية', 'landing', 'cms', 'homepage'],
                'scope' => 'landing',
            ],
            [
                'title' => 'المشاكل',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.problems.index',
                'keywords' => ['مشاكل', 'problems'],
                'scope' => 'landing',
            ],
            [
                'title' => 'الحلول',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.solutions.index',
                'keywords' => ['حلول', 'solutions'],
                'scope' => 'landing',
            ],
            [
                'title' => 'ما نقدمه',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.offerings.index',
                'keywords' => ['نقدمه', 'offerings'],
                'scope' => 'landing',
            ],
            [
                'title' => 'الموديولات',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.modules.index',
                'keywords' => ['موديولات', 'modules'],
                'scope' => 'landing',
            ],
            [
                'title' => 'ميزات الذكاء الاصطناعي',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.ai-features.index',
                'keywords' => ['ذكاء', 'اصطناعي', 'ai', 'features'],
                'scope' => 'landing',
            ],
            [
                'title' => 'الميزات العامة',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.features.index',
                'keywords' => ['ميزات', 'features'],
                'scope' => 'landing',
            ],
            [
                'title' => 'آراء العملاء',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.testimonials.index',
                'keywords' => ['آراء', 'شهادات', 'testimonials'],
                'scope' => 'landing',
            ],
            [
                'title' => 'إعدادات الصفحة الرئيسية',
                'subtitle' => 'محتوى الصفحة الرئيسية',
                'route' => 'admin.landing.settings.edit',
                'keywords' => ['إعدادات', 'settings', 'landing'],
                'scope' => 'landing',
            ],
            [
                'title' => 'الإشعارات',
                'subtitle' => 'التواصل',
                'route' => 'admin.notifications',
                'keywords' => ['إشعارات', 'notifications', 'bell'],
                'scope' => 'notifications',
            ],
            [
                'title' => 'الرسائل والدعم',
                'subtitle' => 'التواصل',
                'route' => 'admin.messages',
                'keywords' => ['رسائل', 'دعم', 'messages', 'support', 'inbox'],
                'scope' => 'messages',
            ],
            [
                'title' => 'النشرة البريدية',
                'subtitle' => 'التواصل',
                'route' => 'admin.newsletter.index',
                'keywords' => ['نشرة', 'بريدية', 'newsletter', 'subscribers'],
                'scope' => 'newsletter',
            ],
            [
                'title' => 'المشتركين',
                'subtitle' => 'النشرة البريدية',
                'route' => 'admin.newsletter.index',
                'keywords' => ['مشتركين', 'مشترك', 'subscribers', 'email'],
                'scope' => 'newsletter',
            ],
            [
                'title' => 'الحملات البريدية',
                'subtitle' => 'النشرة البريدية',
                'route' => 'admin.newsletter.campaigns.index',
                'keywords' => ['حملات', 'حملة', 'campaigns', 'campaign'],
                'scope' => 'newsletter_campaigns',
            ],
            [
                'title' => 'سجل النشاط',
                'subtitle' => 'المنصّة',
                'route' => 'admin.audit-log',
                'keywords' => ['سجل', 'نشاط', 'audit', 'log'],
                'scope' => 'audit',
            ],
            [
                'title' => 'أمان الحساب',
                'subtitle' => 'الحساب والوصول',
                'route' => 'admin.account.security',
                'keywords' => ['أمان', 'security', '2fa'],
                'scope' => 'account',
            ],
            [
                'title' => 'مديرو المنصّة',
                'subtitle' => 'الحساب والوصول',
                'route' => 'admin.admins',
                'keywords' => ['مديرو', 'مشرفين', 'admins'],
                'scope' => 'admins',
            ],
            [
                'title' => 'نتائج البحث',
                'subtitle' => 'المنصّة',
                'route' => 'admin.search',
                'keywords' => ['بحث', 'search'],
                'scope' => 'search',
            ],
        ];
    }

    /**
     * @return list<array{title: string, subtitle: string, url: string, scope: string, anchor: null, mode: string}>
     */
    public function match(string $query, int $limit = 8): array
    {
        $needle = $this->normalize($query);

        if ($needle === '') {
            return [];
        }

        $matches = [];

        foreach ($this->pages() as $page) {
            if (! Route::has($page['route'])) {
                continue;
            }

            $haystack = $this->normalize(implode(' ', [
                $page['title'],
                $page['subtitle'],
                $page['route'],
                implode(' ', $page['keywords']),
            ]));

            if (! str_contains($haystack, $needle)) {
                continue;
            }

            $matches[] = [
                'title' => $page['title'],
                'subtitle' => 'صفحات النظام · '.$page['subtitle'],
                'url' => route($page['route']),
                'scope' => $page['scope'],
                'anchor' => null,
                'mode' => 'navigate',
            ];

            if (count($matches) >= $limit) {
                break;
            }
        }

        return $matches;
    }

    public function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));

        // Strip Arabic tashkeel so «الخُطط» matches «الخطط».
        $stripped = preg_replace('/[\x{064B}-\x{065F}\x{0670}\x{0640}]/u', '', $value);

        return $stripped ?? $value;
    }
}
