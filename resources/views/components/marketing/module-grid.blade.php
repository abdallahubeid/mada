@php
    /* Modules breakdown (docs/MARKETING.md §4.6): core platform modules. */
    $modules = [
        ['title' => 'الموارد البشرية', 'description' => 'الموظفون، العقود، الحضور، والإجازات.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Zm8.25 2.25a2.625 2.625 0 1 1-5.25 0 2.625 2.625 0 0 1 5.25 0Z" />'],
        ['title' => 'المالية والرواتب', 'description' => 'مسير الرواتب، الفوترة، والمصاريف.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 8.25h19.5M2.25 9h19.5m-16.5 5.25h6m-6 2.25h3m-3.75 3h15a1.5 1.5 0 0 0 1.5-1.5V6a1.5 1.5 0 0 0-1.5-1.5h-15A1.5 1.5 0 0 0 3 6v12a1.5 1.5 0 0 0 1.5 1.5Z" />'],
        ['title' => 'المشاريع والعمليات', 'description' => 'كانبان، المهام، وتسجيل الوقت.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 4.5v15m6-15v15M3.75 4.5h16.5A1.5 1.5 0 0 1 21.75 6v12a1.5 1.5 0 0 1-1.5 1.5H3.75A1.5 1.5 0 0 1 2.25 18V6a1.5 1.5 0 0 1 1.5-1.5Z" />'],
        ['title' => 'الدعم والتذاكر', 'description' => 'محادثات الدعم وإدارة الاستفسارات.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" />'],
        ['title' => 'إدارة المستأجرين', 'description' => 'عزل وإدارة دورة حياة كل مؤسسة.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />'],
        ['title' => 'الأمان والصلاحيات', 'description' => 'أدوار دقيقة، 2FA، وسجل نشاط كامل.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6.03 11.959 11.959 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.72A11.959 11.959 0 0 1 12 2.714Z" />'],
    ];
@endphp

<section class="bg-ink-100 py-24 dark:bg-ink-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            eyebrow="الوحدات"
            title="وحدات متكاملة لكل احتياجات مؤسستك"
            subtitle="كل وحدة مصممة لتعمل بتناغم مع البقية، فتنساب البيانات بينها دون جهد."
        />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
            @foreach ($modules as $module)
                <div class="group flex items-start gap-4 rounded-2xl border border-mist-200 bg-white p-6 transition duration-200 ease-out hover:-translate-y-1 hover:border-emerald-400/50 hover:shadow-lg dark:border-ink-800 dark:bg-ink-800/60">
                    <span class="flex h-12 w-12 shrink-0 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-600 transition duration-200 group-hover:bg-emerald-400 group-hover:text-ink-950 dark:text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $module['icon'] !!}</svg>
                    </span>
                    <div class="min-w-0">
                        <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $module['title'] }}</h3>
                        <p class="mt-1 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $module['description'] }}</p>
                    </div>
                </div>
            @endforeach
        </div>
    </div>
</section>
