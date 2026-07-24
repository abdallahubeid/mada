@php
    /* AI capabilities (docs/MARKETING.md §4.8) — clearly labeled ROADMAP, not
       presented as shipped, per the project's phase-honesty discipline. */
    $capabilities = [
        ['title' => 'مساعد ذكي للموارد البشرية', 'description' => 'إجابات فورية عن سياسات الإجازات والرواتب لموظفيك.'],
        ['title' => 'رؤى مالية تنبؤية', 'description' => 'توقّعات ذكية للتدفق النقدي واكتشاف الأنماط غير المعتادة.'],
        ['title' => 'أتمتة سير العمل', 'description' => 'اقتراح وتنفيذ خطوات الموافقة تلقائيًا حسب سياق كل طلب.'],
    ];
@endphp

<section class="relative overflow-hidden bg-ink-950 py-24">
    <div class="pointer-events-none absolute inset-0" aria-hidden="true">
        <div class="absolute top-0 start-1/2 h-72 w-72 -translate-x-1/2 rounded-full bg-emerald-500/15 blur-3xl"></div>
    </div>

    <div class="relative mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
        <div class="mx-auto max-w-2xl text-center">
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold text-emerald-400">
                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span>
                قريباً · خارطة الطريق
            </span>
            <h2 class="mt-4 font-display text-3xl font-bold text-white sm:text-4xl">ذكاء اصطناعي يعمل لصالحك</h2>
            <div class="mx-auto mt-4 h-1 w-16 rounded-full bg-emerald-400"></div>
            <p class="mt-4 text-mist-400">قدرات ذكية قيد التطوير ضمن خارطة طريق Veyra — نشاركك رؤيتنا القادمة بشفافية.</p>
        </div>

        <div class="mt-16 grid gap-6 sm:grid-cols-3">
            @foreach ($capabilities as $cap)
                <div class="relative rounded-2xl border border-ink-800 bg-ink-900/60 p-6">
                    <span class="absolute end-4 top-4 rounded-full bg-ink-800 px-2 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-mist-400">قريباً</span>
                    <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09L15.75 12l-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" /></svg>
                    </div>
                    <h3 class="mt-5 font-display text-lg font-semibold text-white">{{ $cap['title'] }}</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-400">{{ $cap['description'] }}</p>
                </div>
            @endforeach
        </div>
    </div>
</section>
