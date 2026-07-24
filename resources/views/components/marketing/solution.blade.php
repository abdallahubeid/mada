@php
    /* Solution section (docs/MARKETING.md §4.4): how Veyra resolves the pain points. */
    $points = [
        'نظام واحد موحّد يربط الموارد البشرية والمشاريع والرواتب والمالية.',
        'أتمتة كاملة للموافقات وسير العمل بدل العمليات اليدوية.',
        'لوحة تحكم مالية لحظية تمنحك رؤية فورية على أداء مؤسستك.',
        'عزل صارم للبيانات وأمان على مستوى المؤسسة مع سجل نشاط كامل.',
    ];
@endphp

<section class="bg-ink-100 py-24 dark:bg-ink-950">
    <div class="mx-auto grid max-w-7xl gap-16 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
        {{-- Visual --}}
        <div class="relative order-last lg:order-first">
            <div class="rounded-3xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-800 dark:bg-ink-800/60">
                <div class="space-y-4">
                    @foreach (['الموارد البشرية', 'المشاريع', 'الرواتب', 'المالية'] as $i => $module)
                        <div class="flex items-center gap-4 rounded-xl border border-mist-200 bg-ink-50/50 p-4 dark:border-ink-800 dark:bg-ink-900">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-lg bg-emerald-400/15 font-display text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $i + 1 }}</span>
                            <div class="min-w-0 flex-1">
                                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $module }}</p>
                                <div class="mt-2 h-1.5 w-full overflow-hidden rounded-full bg-mist-200 dark:bg-ink-700">
                                    <div class="h-full rounded-full bg-emerald-400" style="width: {{ [90, 75, 85, 70][$i] }}%"></div>
                                </div>
                            </div>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 text-emerald-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75" /></svg>
                        </div>
                    @endforeach
                </div>
            </div>
            <div class="pointer-events-none absolute -inset-4 -z-10 rounded-[2rem] bg-emerald-400/10 blur-2xl" aria-hidden="true"></div>
        </div>

        {{-- Copy --}}
        <div>
            <x-marketing.section-heading
                eyebrow="الحل"
                title="منصّة واحدة تدير كل شيء بسلاسة"
                subtitle="يوحّد Veyra ERP كل عمليات مؤسستك في نظام واحد متكامل، فتختفي الفوضى ويحلّ محلّها الوضوح."
                align="start"
            />

            <ul class="mt-8 space-y-4">
                @foreach ($points as $point)
                    <li class="flex items-start gap-3">
                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-emerald-600 dark:text-emerald-400">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        </span>
                        <span class="text-mist-600 dark:text-mist-300">{{ $point }}</span>
                    </li>
                @endforeach
            </ul>

            <a href="/features" class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-emerald-600 transition hover:gap-3 dark:text-emerald-400">
                اكتشف كل المميزات
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
            </a>
        </div>
    </div>
</section>
