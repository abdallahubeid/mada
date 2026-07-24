@props([
    'hero' => null,
])

@php
    $hero ??= [
        'resolved_metrics' => [
            ['value' => 8500, 'prefix' => '+', 'suffix' => '', 'decimals' => 0, 'label' => 'مستخدم نشط'],
            ['value' => 99.9, 'prefix' => '%', 'suffix' => '', 'decimals' => 1, 'label' => 'نسبة الجاهزية'],
            ['value' => 1200, 'prefix' => '+', 'suffix' => '', 'decimals' => 0, 'label' => 'مؤسسة تثق بنا'],
        ],
    ];
    $metrics = $hero['resolved_metrics'] ?? [];
@endphp

<section class="relative overflow-hidden bg-ink-100 py-20 sm:py-28 dark:bg-ink-950">
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-40 start-1/2 h-[28rem] w-[28rem] -translate-x-1/2 rounded-full bg-emerald-400/15 blur-3xl dark:bg-emerald-400/25"></div>
        <div class="absolute bottom-0 end-0 h-72 w-72 translate-x-1/3 rounded-full bg-emerald-500/10 blur-3xl dark:bg-emerald-500/20"></div>
    </div>

    <div class="relative mx-auto grid max-w-7xl gap-16 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:px-8">
        <div>
            <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold text-emerald-600 dark:text-emerald-400">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.5.04.703.662.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345l2.125-5.111Z" /></svg>
                {{ $settings['hero_badge_text'] ?? '' }}
            </span>

            <h1 class="mt-6 font-display text-4xl font-bold leading-[1.15] text-ink-900 sm:text-5xl lg:text-6xl dark:text-ink-50">
                {{ $settings['hero_title'] ?? '' }}
            </h1>

            <p class="mt-6 max-w-xl text-lg leading-relaxed text-mist-600 dark:text-mist-300">
                {{ $settings['hero_description'] ?? '' }}
            </p>

            <div class="mt-8 flex flex-wrap items-center gap-4">
                <a
                    href="{{ $settings['hero_btn1_link'] ?? '#' }}"
                    class="inline-flex items-center gap-2 rounded-full bg-emerald-500 px-6 py-3 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 ease-in-out hover:bg-emerald-400 active:scale-[0.98]"
                >
                    {{ $settings['hero_btn1_text'] ?? '' }}
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                    </svg>
                </a>
                <a
                    href="{{ $settings['hero_btn2_link'] ?? '#' }}"
                    class="inline-flex items-center gap-2 rounded-full border border-mist-300 px-6 py-3 text-sm font-semibold text-ink-700 transition duration-200 ease-in-out hover:border-emerald-400 hover:text-emerald-600 active:scale-[0.98] dark:border-ink-700 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400"
                >
                    {{ $settings['hero_btn2_text'] ?? '' }}
                </a>
            </div>

            <div class="mt-12 grid grid-cols-3 gap-6 border-t border-mist-200 pt-8 dark:border-ink-800">
                @foreach ($metrics as $metric)
                    <div>
                        <p class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">
                            <x-marketing.stat-counter
                                :value="$metric['value']"
                                :prefix="$metric['prefix']"
                                :suffix="$metric['suffix']"
                                :decimals="$metric['decimals']"
                            />
                        </p>
                        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $metric['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </div>

        <div class="relative">
            <div class="relative rounded-3xl border border-mist-200 bg-white/60 p-2 shadow-2xl backdrop-blur-xl dark:border-ink-800 dark:bg-ink-800/60">
                <div class="overflow-hidden rounded-2xl bg-ink-900">
                    <div class="flex items-center gap-2 border-b border-ink-800 px-4 py-3">
                        <span class="h-2.5 w-2.5 rounded-full bg-danger-solid"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                        <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                        <span class="ms-3 text-xs text-mist-500">app.veyra.com/dashboard</span>
                    </div>
                    <div class="grid grid-cols-3 gap-4 p-6">
                        <div class="col-span-2 rounded-xl bg-ink-800 p-4">
                            <p class="text-xs text-mist-400">اتجاهات النمو السنوي</p>
                            <div class="mt-4 flex h-28 items-end gap-2">
                                <span class="h-[40%] w-full rounded-t-md bg-emerald-400/30"></span>
                                <span class="h-[65%] w-full rounded-t-md bg-emerald-400/40"></span>
                                <span class="h-[45%] w-full rounded-t-md bg-emerald-400/30"></span>
                                <span class="h-[80%] w-full rounded-t-md bg-emerald-400/60"></span>
                                <span class="h-[60%] w-full rounded-t-md bg-emerald-400/40"></span>
                                <span class="h-full w-full rounded-t-md bg-emerald-400"></span>
                            </div>
                        </div>
                        <div class="space-y-3">
                            <div class="rounded-xl bg-ink-800 p-3 text-center">
                                <p class="text-xs text-mist-400">الإيرادات</p>
                                <p class="mt-1 font-display text-lg font-bold text-emerald-400">
                                    <x-marketing.stat-counter :value="458200" />
                                </p>
                            </div>
                            <div class="rounded-xl bg-ink-800 p-3 text-center">
                                <p class="text-xs text-mist-400">المؤسسات</p>
                                <p class="mt-1 font-display text-lg font-bold text-ink-50">
                                    <x-marketing.stat-counter :value="1284" />
                                </p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <div class="animate-hero-float absolute -bottom-6 -start-6 flex max-w-[15rem] items-center gap-3 rounded-2xl border border-mist-200 bg-white/90 p-4 shadow-glow backdrop-blur-xl dark:border-ink-800 dark:bg-ink-800/90">
                <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-400/15 text-emerald-600 dark:text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09l2.846.813-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                    </svg>
                </span>
                <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">مستقبل الأعمال يبدأ هنا</p>
            </div>
        </div>
    </div>
</section>
