@props([
    'title' => 'قوة تتناسب مع طموحاتك',
    'subtitle' => 'كل ما تحتاجه مؤسستك من أدوات إدارية وتشغيلية في نظام واحد متكامل.',
    'features' => null,
])

@php
    /* Core features (docs/MARKETING.md §4.5). Default set reusable on /features. */
    $features ??= [
        [
            'title' => 'أمان متعدد المستأجرين',
            'description' => 'عزل كامل لبيانات كل مؤسسة على مستوى الصفوف، مع سياسات وصول ودورة حياة حساب من 5 مراحل لكل عميل.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6.03 11.959 11.959 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.72A11.959 11.959 0 0 1 12 2.714Z" />',
        ],
        [
            'title' => 'التوظيف وإدارة الموارد البشرية',
            'description' => 'من نشر الوظائف واستقبال المتقدمين إلى العقود والحضور — دورة حياة الموظف كاملة في نظام واحد.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />',
        ],
        [
            'title' => 'المشاريع والعمليات',
            'description' => 'لوحات كانبان، هيكلية استراتيجية، وتسجيل ساعات العمل — لإدارة تنفيذية شاملة لكل مشاريع مؤسستك.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15M3.75 4.5h16.5A1.5 1.5 0 0 1 21.75 6v12a1.5 1.5 0 0 1-1.5 1.5H3.75A1.5 1.5 0 0 1 2.25 18V6a1.5 1.5 0 0 1 1.5-1.5Z" />',
        ],
        [
            'title' => 'الرواتب والتحليلات المالية',
            'description' => 'معالجة رواتب دقيقة، فوترة ومصاريف، ولوحة تحكم مالية تنفيذية تمنحك رؤية فورية على صحة أعمالك.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5h-15A1.5 1.5 0 0 0 3 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />',
        ],
    ];
@endphp

<section id="features" class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading :title="$title" :subtitle="$subtitle" />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($features as $feature)
                <div class="group rounded-2xl border border-mist-200 bg-ink-50/40 p-6 text-start shadow-sm transition duration-200 ease-out hover:-translate-y-1 hover:border-emerald-400/50 hover:shadow-lg dark:border-ink-800 dark:bg-ink-800/60">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-600 transition duration-200 group-hover:bg-emerald-400 group-hover:text-ink-950 dark:text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $feature['icon'] !!}</svg>
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $feature['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $feature['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
