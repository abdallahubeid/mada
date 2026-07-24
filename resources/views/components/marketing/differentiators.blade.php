@php
    /* Why choose us / differentiators (docs/MARKETING.md §4.9). */
    $pillars = [
        ['title' => 'عربي أولاً', 'description' => 'واجهة مصممة أصلاً للعربية بدعم كامل للاتجاه من اليمين لليسار، لا مجرد ترجمة.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m10.5 21 5.25-11.25L21 21m-9-3h7.5M3 5.621a48.474 48.474 0 0 1 6-.371m0 0c1.12 0 2.233.038 3.334.114M9 5.25V3m3.334 2.364C11.176 10.658 7.69 15.08 3 17.502m9.334-12.138c.896.061 1.785.147 2.666.257m-4.589 8.495a18.023 18.023 0 0 1-3.827-5.802" />'],
        ['title' => 'أمان بمعايير المؤسسات', 'description' => 'عزل صارم للبيانات، تحقق بخطوتين إلزامي، وتشفير للأسرار الحساسة.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6.03 11.959 11.959 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.72A11.959 11.959 0 0 1 12 2.714Z" />'],
        ['title' => 'إعداد سريع', 'description' => 'فعّل مؤسستك وابدأ العمل في نفس اليوم مع تدفق إعداد موجّه وأدوات استيراد.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 13.5 10.5 21m0 0 6.75-7.5M10.5 21V3" />'],
        ['title' => 'دعم يتحدث لغتك', 'description' => 'فريق دعم عربي متجاوب على مدار الساعة للخطط الأعلى، وقاعدة معرفية شاملة.', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M8.625 9.75a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />'],
    ];
@endphp

<section class="bg-ink-100 py-24 dark:bg-ink-950">
    <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <x-marketing.section-heading
            eyebrow="لماذا Veyra"
            title="ما الذي يميّزنا عن غيرنا"
            subtitle="لم نبنِ مجرد أداة أخرى، بل منصّة تفهم طبيعة المؤسسات في منطقتنا."
        />

        <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($pillars as $pillar)
                <div class="rounded-2xl border border-mist-200 bg-white p-6 text-center transition duration-200 ease-out hover:-translate-y-1 hover:border-emerald-400/50 hover:shadow-lg dark:border-ink-800 dark:bg-ink-800/60">
                    <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-emerald-400/10 text-emerald-600 dark:text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $pillar['icon'] !!}</svg>
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $pillar['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $pillar['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
