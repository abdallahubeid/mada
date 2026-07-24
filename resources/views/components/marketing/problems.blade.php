@php
    /* Problems section (docs/MARKETING.md §4.3): pain points without a modern ERP. */
    $problems = [
        [
            'title' => 'أنظمة متفرقة لا تتحدث معًا',
            'description' => 'بيانات الموارد البشرية في مكان، والرواتب في آخر، والمشاريع في جداول منفصلة — ما يعني ازدواجية وأخطاء وضياع للوقت.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M13.19 8.688a4.5 4.5 0 0 1 1.242 7.244l-4.5 4.5a4.5 4.5 0 0 1-6.364-6.364l1.757-1.757m13.35-.622 1.757-1.757a4.5 4.5 0 0 0-6.364-6.364l-4.5 4.5a4.5 4.5 0 0 0 1.242 7.244" />',
        ],
        [
            'title' => 'عمليات يدوية تستنزف الفرق',
            'description' => 'الموافقات والمتابعات عبر البريد والورق تبطئ اتخاذ القرار وتُنهك موظفيك في مهام متكررة بلا قيمة.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
        ],
        [
            'title' => 'غياب الرؤية المالية اللحظية',
            'description' => 'بدون لوحة تحكم موحّدة تفقد القدرة على قراءة صحة أعمالك في الوقت المناسب لاتخاذ قرارات دقيقة.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />',
        ],
        [
            'title' => 'مخاوف أمنية على البيانات',
            'description' => 'مشاركة البيانات الحساسة عبر أدوات غير آمنة تعرّض مؤسستك لمخاطر تسريب وفقدان للثقة.',
            'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />',
        ],
    ];
@endphp

<section class="bg-white py-24 dark:bg-ink-900">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            eyebrow="التحديات"
            title="هل تبدو هذه المشاكل مألوفة؟"
            subtitle="معظم المؤسسات تُدار عبر أدوات متفرقة تخلق فوضى تشغيلية بدل أن تحلّها."
        />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($problems as $problem)
                <div class="rounded-2xl border border-mist-200 bg-ink-50/40 p-6 transition duration-200 ease-out hover:-translate-y-1 hover:border-danger-solid/40 dark:border-ink-800 dark:bg-ink-800/40">
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-danger-solid/10 text-danger-solid">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $problem['icon'] !!}</svg>
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $problem['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $problem['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
