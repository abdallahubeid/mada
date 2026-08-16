{{-- Solutions page (docs/MARKETING.md §2) — industry-anchored sections. --}}
<x-layouts.marketing
    title="الحلول — مدى"
    description="حلول مدى مصممة للمنظمات غير الربحية والجمعيات الخيرية والشركات الصغيرة والمؤسسات التعليمية والجهات الحكومية."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="الحلول"
            title="مصمّم لطبيعة مؤسستك"
            subtitle="سواء كنت جمعية خيرية أو جهة حكومية أو شركة نامية — مدى يتكيّف مع متطلبات قطاعك."
        />

        {{-- Industry jump links --}}
        <section class="border-b border-mist-200 bg-white py-6 dark:border-ink-800 dark:bg-ink-900">
            <div class="mx-auto flex max-w-7xl flex-wrap items-center justify-center gap-2 px-4 sm:px-6 lg:px-8">
                @foreach ($industries as $industry)
                    <a
                        href="#{{ $industry['id'] }}"
                        class="rounded-md border border-mist-200 px-4 py-1.5 text-sm font-medium text-ink-600 transition duration-200 hover:border-brand-500 hover:text-brand-600 dark:border-ink-700 dark:text-mist-300 dark:hover:border-brand-500 dark:hover:text-brand-300"
                    >{{ $industry['title'] }}</a>
                @endforeach
            </div>
        </section>

        @foreach ($industries as $index => $industry)
            <section
                id="{{ $industry['id'] }}"
                @class([
                    'scroll-mt-20 py-24 sm:py-24',
                    'bg-ink-100 dark:bg-ink-950' => $index % 2 === 0,
                    'bg-white dark:bg-ink-900' => $index % 2 === 1,
                ])
            >
                <div class="mx-auto grid max-w-7xl gap-12 px-4 sm:px-6 lg:grid-cols-2 lg:items-center lg:gap-16 lg:px-8">
                    <div @class(['lg:order-last' => $index % 2 === 1])>
                        <span class="inline-flex items-center gap-2 rounded-md border border-brand-500/30 bg-brand-500/10 px-4 py-1.5 text-xs font-semibold text-brand-600 dark:text-brand-300">
                            {{ $industry['tagline'] }}
                        </span>
                        <h2 class="mt-4 font-display text-2xl font-medium text-ink-900 sm:text-3xl dark:text-ink-50">
                            {{ $industry['title'] }}
                        </h2>
                        <p class="mt-4 text-mist-600 dark:text-mist-300">{{ $industry['description'] }}</p>

                        <ul class="mt-8 space-y-3">
                            @foreach ($industry['points'] as $point)
                                <li class="flex items-start gap-3">
                                    <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-brand-500/15 text-brand-600 dark:text-brand-300">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                    </span>
                                    <span class="text-sm text-mist-600 dark:text-mist-300">{{ $point }}</span>
                                </li>
                            @endforeach
                        </ul>

                        <a
                            href="{{ route('register') }}"
                            class="mt-8 inline-flex items-center gap-2 text-sm font-semibold text-brand-600 transition hover:gap-3 dark:text-brand-300"
                        >
                            ابدأ التجربة المجانية
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" /></svg>
                        </a>
                    </div>

                    <div class="relative">
                        <div class="rounded-3xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-800 dark:bg-ink-800/60">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-brand-500/10 font-display text-lg font-medium text-brand-600 dark:text-brand-300">
                                {{ $index + 1 }}
                            </div>
                            <p class="mt-6 font-display text-xl font-medium text-ink-900 dark:text-ink-50">{{ $industry['title'] }}</p>
                            <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $industry['tagline'] }}</p>
                            <div class="mt-6 space-y-2">
                                @foreach ($industry['points'] as $i => $point)
                                    <div class="h-2 rounded-full bg-mist-200 dark:bg-ink-700">
                                        <div class="h-full rounded-full bg-brand-500" style="width: {{ 90 - ($i * 8) }}%"></div>
                                    </div>
                                @endforeach
                            </div>
                        </div>
                        <div class="pointer-events-none absolute -inset-4 -z-10 rounded-[2rem] bg-brand-500/10 blur-2xl" aria-hidden="true"></div>
                    </div>
                </div>
            </section>
        @endforeach

        <x-marketing.cta-band
            title="هل قطاعك مختلف؟"
            subtitle="تواصل معنا لنصمّم مسارًا يناسب متطلبات مؤسستك."
            secondary-label="احجز عرضًا توضيحيًا"
            secondary-href="/contact"
        />
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
