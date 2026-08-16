<?php

/**
 * Public marketing page copy previously stored in platform_settings.
 * Consumed by MarketingContent (docs/MARKETING.md).
 */
return [
    'hero' => [
        'eyebrow' => 'منصة SaaS متكاملة لإدارة المؤسسات',
        'title_line_1' => 'مستقبل إدارة',
        'title_accent' => 'المؤسسات',
        'title_line_2' => 'بذكاء وفخامة',
        'subtitle' => 'منصة مدى الشاملة لإدارة الموارد البشرية، المشاريع، والرواتب — أتمتة كاملة لعمليات مؤسستك في نظام واحد أنيق وذكي، بدقة تنظيمية وأمان تام لبياناتك.',
        'primary_cta' => ['label' => 'ابدأ التجربة المجانية', 'url' => '/register'],
        'secondary_cta' => ['label' => 'احجز عرضًا توضيحيًا', 'url' => '/contact'],
        'metrics' => [
            ['key' => 'active_users', 'source' => 'live', 'prefix' => '+', 'fallback' => 8500, 'label' => 'مستخدم نشط'],
            ['key' => 'uptime', 'source' => 'cms', 'prefix' => '%', 'value' => 99.9, 'decimals' => 1, 'label' => 'نسبة الجاهزية'],
            ['key' => 'active_tenants', 'source' => 'live', 'prefix' => '+', 'fallback' => 1200, 'label' => 'مؤسسة تثق بنا'],
        ],
    ],

    'partners_fallback' => [
        'eyebrow' => 'موثوق من قبل مؤسسات رائدة',
        'names' => ['TechNova', 'Al-Manar', 'Global Corp', 'Saudi Vision', 'Emirates Lux', 'Nova Bank', 'Riyadh Tech'],
    ],

    'cta' => [
        'title' => 'جاهز لتحويل مؤسستك؟',
        'subtitle' => 'ابدأ تجربتك المجانية اليوم — دون بطاقة ائتمان، وبإعداد يستغرق دقائق.',
        'primary' => ['label' => 'ابدأ التجربة المجانية', 'url' => '/register'],
        'secondary' => ['label' => 'تواصل مع المبيعات', 'url' => '/contact'],
    ],

    'footer' => [
        'blurb' => 'نظام إدارة الموارد المؤسسي الذكي الذي يواكب طموحات مؤسستك القادمة.',
        'copyright' => '© {year} مدى. جميع الحقوق محفوظة.',
        'social' => [
            ['platform' => 'x', 'label' => 'X', 'url' => '#'],
            ['platform' => 'linkedin', 'label' => 'LinkedIn', 'url' => '#'],
        ],
        'columns' => [
            [
                'title' => 'المنتج',
                'links' => [
                    ['label' => 'المميزات', 'url' => '/features'],
                    ['label' => 'الحلول', 'url' => '/solutions'],
                    ['label' => 'الأسعار', 'url' => '/pricing'],
                    ['label' => 'الأمان والامتثال', 'url' => '/security'],
                ],
            ],
            [
                'title' => 'الشركة',
                'links' => [
                    ['label' => 'من نحن', 'url' => '/about'],
                    ['label' => 'تواصل معنا', 'url' => '/contact'],
                    ['label' => 'الأسئلة الشائعة', 'url' => '/faq'],
                ],
            ],
            [
                'title' => 'القانونية',
                'links' => [
                    ['label' => 'سياسة الخصوصية', 'url' => '/privacy'],
                    ['label' => 'الشروط والأحكام', 'url' => '/terms'],
                ],
            ],
        ],
    ],

    'features' => [
        'title' => 'قوة تتناسب مع طموحاتك',
        'subtitle' => 'كل ما تحتاجه مؤسستك من أدوات إدارية وتشغيلية في نظام واحد متكامل.',
    ],

    'uptime' => 99.9,

    'product_preview' => [
        'revenue_k' => 458,
    ],
];
