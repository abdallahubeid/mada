{{-- Security & Compliance (docs/MARKETING.md §2) in the Andromeda style. --}}
@php
    $lifecycle = [
        'بانتظار التحقق',
        'بانتظار الموافقة',
        'نشط',
        'موقوف',
        'ملغى',
    ];
@endphp

<x-layouts.marketing
    title="الأمان والامتثال — مدى"
    description="عزل بيانات متعدد المستأجرين، تحقق بخطوتين إلزامي، سجل نشاط قابل للتدقيق، وتشفير للأسرار — أمان بمعايير المؤسسات."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="الأمان والامتثال"
            title="أمان مدمج في بنية المنصّة"
            subtitle="صُمّم مدى من الأساس لعزل البيانات والحوكمة والتدقيق — خاصة للجهات الحكومية والتعليمية وغير الربحية."
        />

        {{-- The pillars, as the template's feature cards. --}}
        <section class="site-section">
            <div class="site-container text-center">
                <x-marketing.section-heading
                    title="ركائز الأمان في مدى"
                    subtitle="ضمانات تقنية وتشغيلية يمكنك الاعتماد عليها عند تقييم المنصّة."
                />

                <div class="mt-10 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($pillars as $pillar)
                        <article class="site-feature-card">
                            <span class="site-feature-card-icon">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-8 w-8" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">{!! $pillar['icon'] !!}</svg>
                            </span>
                            <h3 class="site-h4 mt-6 mb-5">{{ $pillar['title'] }}</h3>
                            <p>{{ $pillar['description'] }}</p>
                        </article>
                    @endforeach
                </div>
            </div>
        </section>

        {{--
            The tenant lifecycle is a real sequence, so it is the one place on
            the site that gets numbered steps. It sits on the template's peach
            panel with waves and circles.
        --}}
        <section class="pb-16">
            <div class="site-container-xl">
                <div class="site-panel-host px-4 py-[70px]">
                    <x-marketing.panel circles="section" />

                    <x-marketing.section-heading
                        eyebrow="دورة حياة المستأجر"
                        title="خمس مراحل قبل منح الوصول الكامل"
                        subtitle="لا يُفتح النظام التشغيلي إلا بعد التحقق والموافقة — تحكم واضح في كل مرحلة."
                    />

                    <ol class="mx-auto mt-10 grid max-w-5xl gap-4 sm:grid-cols-5">
                        @foreach ($lifecycle as $i => $state)
                            <li class="site-card p-5 text-center">
                                <span class="mx-auto flex h-10 w-10 items-center justify-center rounded-full bg-site-primary-action font-semibold text-white">{{ $i + 1 }}</span>
                                <p class="mt-3 font-medium text-site-dark">{{ $state }}</p>
                            </li>
                        @endforeach
                    </ol>
                </div>
            </div>
        </section>

        <x-marketing.cta-band
            title="هل لديك متطلبات امتثال خاصة؟"
            subtitle="فريقنا جاهز لمناقشة متطلبات الأمان والخصوصية لمؤسستك."
            primary-label="تواصل معنا"
            primary-href="/contact"
            secondary-label="ابدأ التجربة المجانية"
            :secondary-href="route('register')"
        />
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
