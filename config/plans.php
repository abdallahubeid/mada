<?php

/*
 * Single source of truth for public plan tiers (docs/MARKETING.md §3).
 * Consumed by the marketing pricing table and landing preview. Tier names are
 * locked to Startup / Growth / Enterprise to match the admin console and docs.
 * Prices are in USD; `monthly`/`yearly` null means "contact sales".
 */

return [
    'currency' => '$',

    'tiers' => [
        [
            'name' => 'Startup',
            'tagline' => 'للشركات الناشئة والفرق الصغيرة',
            'monthly' => 49,
            'yearly' => 39,
            'cta' => 'ابدأ الآن',
            'href' => '/register',
            'highlighted' => false,
            'features' => [
                'حتى 10 مستخدمين',
                'الموارد البشرية الأساسية',
                'دعم عبر البريد الإلكتروني',
                'تخصيص محدود للواجهة',
            ],
        ],
        [
            'name' => 'Growth',
            'tagline' => 'للمؤسسات المتوسطة سريعة النمو',
            'monthly' => 129,
            'yearly' => 103,
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
