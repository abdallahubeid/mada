@extends('tenant.portal.layout')

@section('title', $company['name'].' — بوابة التوظيف')

@section('content')
    @if ($portalSettings->isSectionActive('hero'))
        <section class="relative overflow-hidden portal-grid-bg">
            <div class="pointer-events-none absolute -start-24 top-10 h-72 w-72 rounded-full bg-emerald-400/20 blur-3xl"></div>
            <div class="pointer-events-none absolute -end-16 bottom-0 h-64 w-64 rounded-full bg-emerald-400/10 blur-3xl"></div>

            <div class="relative mx-auto grid max-w-6xl items-center gap-10 px-4 py-16 sm:px-6 sm:py-24 lg:grid-cols-2">
                <div>
                    @if (filled($portalSettings->hero_badge_text))
                        <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/40 bg-emerald-400/10 px-3 py-1 text-xs font-bold text-emerald-700 shadow-[0_0_20px_rgba(78,222,163,0.2)] dark:text-emerald-300">
                            <span class="relative flex h-2 w-2">
                                <span class="absolute inline-flex h-full w-full animate-ping rounded-full bg-emerald-400 opacity-60"></span>
                                <span class="relative inline-flex h-2 w-2 rounded-full bg-emerald-500"></span>
                            </span>
                            {{ $portalSettings->hero_badge_text }}
                        </span>
                    @endif
                    <h1 class="mt-6 font-display text-4xl font-bold tracking-tight text-ink-900 sm:text-5xl dark:text-ink-50">
                        {{ $portalSettings->hero_title }}
                    </h1>
                    <p class="mt-4 max-w-xl text-base leading-relaxed text-mist-500 dark:text-mist-400 sm:text-lg">
                        {{ $portalSettings->hero_subtitle }}
                    </p>
                    <div class="mt-8 flex flex-col gap-3 sm:flex-row">
                        @if (filled($portalSettings->hero_primary_cta_text))
                            <a href="{{ $heroPrimaryUrl }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-6 py-3 text-sm font-bold text-emerald-950 shadow-[0_0_32px_rgba(78,222,163,0.35)] transition hover:bg-emerald-300">
                                {{ $portalSettings->hero_primary_cta_text }}
                            </a>
                        @endif
                        @if (filled($portalSettings->hero_secondary_cta_text))
                            <a href="{{ $heroSecondaryUrl }}" class="inline-flex items-center justify-center rounded-xl border border-mist-200 bg-white/80 px-6 py-3 text-sm font-semibold text-ink-700 backdrop-blur transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600 dark:bg-ink-900/60 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400">
                                {{ $portalSettings->hero_secondary_cta_text }}
                            </a>
                        @endif
                    </div>
                </div>

                @if ($portalSettings->isSectionActive('stats'))
                    <div class="relative">
                        <div class="absolute -inset-4 rounded-[2rem] bg-emerald-400/15 blur-2xl"></div>
                        <div class="relative overflow-hidden rounded-[1.75rem] border border-white/40 bg-white/70 p-6 shadow-xl backdrop-blur-xl dark:border-ink-600/60 dark:bg-ink-900/50">
                            <div class="grid grid-cols-2 gap-3">
                                @foreach ($portalSettings->stats() as $stat)
                                    <div class="rounded-2xl border border-mist-200/80 bg-white/90 p-4 transition dark:border-ink-600 dark:bg-ink-800/80">
                                        <p class="font-display text-3xl font-bold text-ink-900 dark:text-ink-50">{{ $stat['value'] }}{{ $stat['suffix'] }}</p>
                                        <p class="mt-1 text-xs font-medium text-mist-500">{{ $stat['label'] }}</p>
                                    </div>
                                @endforeach
                                @if ($portalSettings->isSectionActive('careers'))
                                    <div class="rounded-2xl border border-emerald-400/30 bg-emerald-400/10 p-4">
                                        <p class="text-xs font-semibold text-emerald-700 dark:text-emerald-300">فرص مفتوحة</p>
                                        <p class="mt-2 font-display text-3xl font-bold text-emerald-600 dark:text-emerald-400">{{ count($jobs) }}</p>
                                    </div>
                                @endif
                            </div>
                        </div>
                    </div>
                @endif
            </div>
        </section>
    @endif

    @if ($portalSettings->isSectionActive('about'))
        <section id="about" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-xs font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">{{ $portalSettings->about_subtitle }}</p>
                <h2 class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50">{{ $portalSettings->about_title }}</h2>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                <article class="group rounded-2xl border border-mist-200/80 portal-glass p-6 shadow-sm veyra-card hover:border-emerald-400/40 dark:border-ink-600/70">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-400/15 text-emerald-600 dark:text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    </div>
                    <h3 class="mt-4 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الرؤية</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $portalSettings->vision_text }}</p>
                </article>
                <article class="group rounded-2xl border border-mist-200/80 portal-glass p-6 shadow-sm veyra-card hover:border-emerald-400/40 dark:border-ink-600/70">
                    <div class="flex h-11 w-11 items-center justify-center rounded-xl bg-emerald-400/15 text-emerald-600 dark:text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.59 14.37a6 6 0 0 1-5.84 7.38v-4.8m5.84-2.58a14.98 14.98 0 0 0 6.16-12.12A14.98 14.98 0 0 0 9.63 8.41m5.96 5.96a14.926 14.926 0 0 1-5.841 2.58m-.119-8.54a6 6 0 0 0-7.381 5.84h4.8m2.581-5.84a14.927 14.927 0 0 0-2.58 5.84m2.699 2.7c-.103.021-.207.041-.311.06a15.09 15.09 0 0 1-2.448-2.448 14.9 14.9 0 0 1 .06-.312m-2.24 2.39a6.042 6.042 0 0 1-2.39-2.39" /></svg>
                    </div>
                    <h3 class="mt-4 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الرسالة</h3>
                    <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $portalSettings->mission_text }}</p>
                </article>
            </div>
            <div class="mt-4 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                @foreach ($portalSettings->values() as $value)
                    <article class="rounded-2xl border border-mist-200/80 bg-white/80 p-5 shadow-sm veyra-card hover:border-emerald-400/40 dark:border-ink-600 dark:bg-ink-800/70">
                        <h3 class="font-display text-base font-semibold text-emerald-600 dark:text-emerald-400">{{ $value['title'] }}</h3>
                        <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">{{ $value['desc'] }}</p>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($portalSettings->isSectionActive('services'))
        <section id="services" class="border-y border-mist-200/70 bg-white/50 dark:border-ink-700/70 dark:bg-ink-900/30">
            <div class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
                <div class="max-w-2xl">
                    <p class="text-xs font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">{{ $portalSettings->services_subtitle }}</p>
                    <h2 class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50">{{ $portalSettings->services_title }}</h2>
                </div>
                <div class="mt-8 grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    @foreach ($portalSettings->services() as $service)
                        <article class="group relative overflow-hidden rounded-2xl border border-mist-200 bg-white p-5 shadow-sm veyra-card hover:border-emerald-400/50 hover:shadow-[0_0_30px_rgba(78,222,163,0.12)] dark:border-ink-600 dark:bg-ink-800">
                            <div class="absolute -end-6 -top-6 h-20 w-20 rounded-full bg-emerald-400/10 transition group-hover:bg-emerald-400/20"></div>
                            <div class="relative flex h-10 w-10 items-center justify-center rounded-xl border border-emerald-400/30 bg-emerald-400/10 text-xs font-bold uppercase text-emerald-600 dark:text-emerald-400">
                                {{ \Illuminate\Support\Str::limit($service['icon'] ?? 'ops', 4, '') }}
                            </div>
                            <h3 class="relative mt-4 font-display text-base font-semibold text-ink-900 dark:text-ink-50">{{ $service['title'] }}</h3>
                            <p class="relative mt-2 text-sm text-mist-500 dark:text-mist-400">{{ $service['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>
    @endif

    @if ($portalSettings->isSectionActive('culture'))
        <section id="culture" class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="max-w-2xl">
                <p class="text-xs font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">{{ $portalSettings->culture_subtitle }}</p>
                <h2 class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50">{{ $portalSettings->culture_title }}</h2>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-2">
                @foreach ($portalSettings->culturePerks() as $perk)
                    <article class="flex gap-4 rounded-2xl border border-mist-200/80 portal-glass p-5 shadow-sm transition duration-300 hover:border-emerald-400/40 dark:border-ink-600/70">
                        <span class="mt-0.5 flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-400 text-sm font-bold text-emerald-950 shadow-[0_0_18px_rgba(78,222,163,0.35)]">✓</span>
                        <div>
                            <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">{{ $perk['title'] }}</h3>
                            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $perk['description'] }}</p>
                        </div>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($portalSettings->isSectionActive('stats') && ! $portalSettings->isSectionActive('hero'))
        <section class="relative overflow-hidden border-y border-mist-200 dark:border-ink-700">
            <div class="absolute inset-0 portal-grid-bg opacity-70"></div>
            <div class="relative mx-auto max-w-6xl px-4 py-12 sm:px-6">
                @if (filled($portalSettings->stats_title))
                    <h2 class="mb-6 text-center font-display text-2xl font-bold text-ink-900 dark:text-ink-50">{{ $portalSettings->stats_title }}</h2>
                @endif
                <div class="grid gap-4 sm:grid-cols-3">
                    @foreach ($portalSettings->stats() as $stat)
                        <div class="rounded-2xl border border-mist-200/80 bg-ink-950 p-6 text-center text-white shadow-lg dark:border-emerald-400/20">
                            <p class="font-display text-4xl font-bold text-emerald-400">{{ $stat['value'] }}{{ $stat['suffix'] }}</p>
                            <p class="mt-2 text-sm font-medium text-mist-300">{{ $stat['label'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>
    @elseif ($portalSettings->isSectionActive('stats'))
        <section class="relative overflow-hidden border-y border-mist-200 dark:border-ink-700">
            <div class="absolute inset-0 portal-grid-bg opacity-70"></div>
            <div class="relative mx-auto grid max-w-6xl gap-4 px-4 py-12 sm:grid-cols-3 sm:px-6">
                @foreach ($portalSettings->stats() as $stat)
                    <div class="rounded-2xl border border-mist-200/80 bg-ink-950 p-6 text-center text-white shadow-lg dark:border-emerald-400/20">
                        <p class="font-display text-4xl font-bold text-emerald-400">{{ $stat['value'] }}{{ $stat['suffix'] }}</p>
                        <p class="mt-2 text-sm font-medium text-mist-300">{{ $stat['label'] }}</p>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($portalSettings->isSectionActive('careers'))
        <section class="mx-auto max-w-6xl px-4 py-16 sm:px-6">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
                <div>
                    <p class="text-xs font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">{{ $portalSettings->careers_badge_text }}</p>
                    <h2 class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50">{{ $portalSettings->careers_title }}</h2>
                    @if (filled($portalSettings->careers_subtitle))
                        <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">{{ $portalSettings->careers_subtitle }}</p>
                    @endif
                </div>
                <a href="{{ route('portal.careers', $slug) }}" class="text-sm font-bold text-emerald-600 hover:text-emerald-500 dark:text-emerald-400">كل الشواغر ←</a>
            </div>
            <div class="mt-8 grid gap-4 md:grid-cols-3">
                @foreach (array_slice($jobs, 0, 3) as $job)
                    <article class="group flex flex-col rounded-2xl border border-mist-200 bg-white p-5 shadow-sm veyra-card hover:border-emerald-400/50 hover:shadow-[0_12px_40px_rgba(15,23,42,0.08)] dark:border-ink-600 dark:bg-ink-800 dark:hover:shadow-[0_12px_40px_rgba(0,0,0,0.35)]">
                        <span class="w-fit rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">{{ $job['department'] }}</span>
                        <h3 class="mt-3 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $job['title'] }}</h3>
                        <p class="mt-2 flex-1 text-sm text-mist-500 dark:text-mist-400">{{ $job['summary'] }}</p>
                        <p class="mt-3 text-xs text-mist-400">{{ $job['location'] }} · {{ $job['type'] }}</p>
                        <a href="{{ route('portal.jobs.show', [$slug, $job['slug']]) }}" class="mt-4 inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-bold text-emerald-950 transition group-hover:bg-emerald-300">
                            تقديم سريع
                        </a>
                    </article>
                @endforeach
            </div>
        </section>
    @endif

    @if ($portalSettings->isSectionActive('faq'))
        <section class="mx-auto max-w-3xl px-4 pb-8 sm:px-6">
            <div class="text-center">
                <p class="text-xs font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">{{ $portalSettings->faq_subtitle }}</p>
                <h2 class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50">{{ $portalSettings->faq_title }}</h2>
            </div>
            <div class="mt-8 space-y-3" x-data="{ open: 0 }">
                @foreach ($portalSettings->faqs() as $index => $faq)
                    <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white dark:border-ink-600 dark:bg-ink-800">
                        <button
                            type="button"
                            class="flex w-full items-center justify-between gap-3 px-5 py-4 text-start"
                            @click="open = open === {{ $index }} ? null : {{ $index }}"
                        >
                            <span class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $faq['question'] }}</span>
                            <span class="text-emerald-500 transition" :class="open === {{ $index }} && 'rotate-180'">⌄</span>
                        </button>
                        <div
                            x-show="open === {{ $index }}"
                            x-cloak
                            x-transition
                            class="border-t border-mist-100 px-5 py-4 text-sm leading-relaxed text-mist-500 dark:border-ink-700 dark:text-mist-400"
                        >
                            {{ $faq['answer'] }}
                        </div>
                    </div>
                @endforeach
            </div>
        </section>
    @endif

    @if ($portalSettings->isSectionActive('cta'))
        <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <div class="relative overflow-hidden rounded-[1.75rem] bg-gradient-to-l from-emerald-500 via-emerald-400 to-emerald-300 px-6 py-12 text-center shadow-[0_0_60px_rgba(78,222,163,0.35)] sm:px-10">
                <div class="pointer-events-none absolute inset-0 opacity-30" style="background-image: linear-gradient(rgba(15,23,42,0.08) 1px, transparent 1px), linear-gradient(90deg, rgba(15,23,42,0.08) 1px, transparent 1px); background-size: 32px 32px;"></div>
                <div class="relative">
                    <h2 class="font-display text-2xl font-bold text-ink-950 sm:text-3xl">{{ $portalSettings->cta_title }}</h2>
                    <p class="mx-auto mt-3 max-w-xl text-sm text-emerald-950/80">{{ $portalSettings->cta_subtitle }}</p>
                    <div class="mt-6 flex flex-col items-center justify-center gap-3 sm:flex-row">
                        @if (filled($portalSettings->cta_button_text))
                            <a href="{{ $ctaButtonUrl }}" class="inline-flex rounded-xl bg-ink-950 px-6 py-3 text-sm font-bold text-white transition hover:bg-ink-800">{{ $portalSettings->cta_button_text }}</a>
                        @endif
                        @if ($portalSettings->isSectionActive('contact'))
                            <a href="{{ route('portal.contact', $slug) }}" class="inline-flex rounded-xl border border-ink-950/20 bg-white/40 px-6 py-3 text-sm font-bold text-ink-950 backdrop-blur transition hover:bg-white/70">تحدث معنا</a>
                        @endif
                    </div>
                </div>
            </div>
        </section>
    @endif
@endsection
