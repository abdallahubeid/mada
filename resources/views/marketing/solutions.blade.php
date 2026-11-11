{{-- Solutions page (docs/MARKETING.md §2) — industry-anchored sections in the Andromeda "speciality" row style. --}}
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

        <nav class="site-container pt-12" aria-label="القطاعات">
            <ul class="flex flex-wrap items-center justify-center gap-2">
                @foreach ($industries as $industry)
                    <li>
                        <a href="#{{ $industry['id'] }}" class="site-btn site-btn-outline min-h-0 px-4 py-2">{{ $industry['title'] }}</a>
                    </li>
                @endforeach
            </ul>
        </nav>

        @foreach ($industries as $index => $industry)
            @php $flip = $index % 2 === 1; @endphp

            <section id="{{ $industry['id'] }}" class="site-section">
                <div class="site-container">
                    <div class="grid items-center gap-12 lg:grid-cols-2 lg:gap-16">
                        <div @class(['lg:order-last' => $flip])>
                            <p>{{ $industry['tagline'] }}</p>
                            <h2 class="site-section-title site-section-title--start mt-4">{{ $industry['title'] }}</h2>
                            <p class="mt-10">{{ $industry['description'] }}</p>

                            <ul class="mt-8 space-y-3">
                                @foreach ($industry['points'] as $point)
                                    <li class="flex items-start gap-3">
                                        <span class="mt-0.5 flex h-6 w-6 shrink-0 items-center justify-center rounded-full bg-site-border-soft text-site-primary">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                        </span>
                                        <span>{{ $point }}</span>
                                    </li>
                                @endforeach
                            </ul>

                            <a href="{{ route('register') }}" class="site-btn site-btn-primary mt-10">ابدأ التجربة المجانية</a>
                        </div>

                        <div class="relative isolate p-6 sm:p-[60px]" aria-hidden="true">
                            <x-marketing.circles set="figure" />

                            <div class="site-card relative p-7">
                                <span class="flex h-14 w-14 items-center justify-center rounded-md border border-site-peach bg-site-cream text-xl font-semibold text-site-primary-ink">{{ $index + 1 }}</span>
                                <p class="site-h4 mt-6 font-semibold text-site-dark">{{ $industry['title'] }}</p>
                                <p class="mt-2">{{ $industry['tagline'] }}</p>
                                <div class="mt-6 space-y-3">
                                    @foreach ($industry['points'] as $i => $point)
                                        <div class="h-2 rounded-full bg-site-border-soft">
                                            <div class="h-full rounded-full bg-site-primary" style="width: {{ 90 - ($i * 8) }}%"></div>
                                        </div>
                                    @endforeach
                                </div>
                            </div>
                        </div>
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
