<?php

/*
 * Fallback plan tiers when the database has no active plans (docs/MARKETING.md §3).
 * Primary source: `plans` + `plan_features` tables via PlanSeeder.
 */

return [
    'currency' => '$',

    'tiers' => [
        [
            'name' => 'الأساسية',
            'tagline' => 'للشركات الناشئة والمؤسسات الصغيرة',
            'monthly' => 49,
            'yearly' => 39,
            'cta' => 'ابدأ الآن',
            'href' => '/register',
            'highlighted' => false,
            'features' => [
                'حتى 10 مستخدمين',
                'إدارة الموارد البشرية والرواتب',
                'دعم عبر البريد الإلكتروني',
                'تقارير أساسية',
            ],
        ],
        [
            'name' => 'النمو',
            'tagline' => 'للمؤسسات المتوسطة سريعة النمو',
            'monthly' => 129,
            'yearly' => 99,
            'cta' => 'ابدأ الآن',
            'href' => '/register',
            'highlighted' => true,
            'features' => [
                'حتى 100 مستخدم',
                'الرواتب والتوظيف الكاملة',
                'دعم أولوية على مدار الساعة',
                'تقارير وتحليلات متقدمة',
                'صلاحيات وأدوار مخصصة',
            ],
        ],
        [
            'name' => 'Enterprise',
            'tagline' => 'للمؤسسات الكبيرة ومتطلبات مخصصة',
            'monthly' => null,
            'yearly' => null,
            'cta' => 'تواصل مع المبيعات',
            'href' => '/contact',
            'highlighted' => false,
            'features' => [
                'مستخدمين غير محدودين',
                'تكامل الذكاء الاصطناعي',
                'مدير حساب مخصص',
                'استضافة خاصة (On-Premise)',
            ],
        ],
    ],
];
