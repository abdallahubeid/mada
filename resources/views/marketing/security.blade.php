{{-- Security & Compliance page (docs/MARKETING.md §2). --}}
<x-layouts.marketing
    title="الأمان والامتثال — Veyra ERP"
    description="عزل بيانات متعدد المستأجرين، تحقق بخطوتين إلزامي، سجل نشاط قابل للتدقيق، وتشفير للأسرار — أمان بمعايير المؤسسات."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="الأمان والامتثال"
            title="أمان مدمج في بنية المنصّة"
            subtitle="صُمّم Veyra من الأساس لعزل البيانات والحوكمة والتدقيق — خاصة للجهات الحكومية والتعليمية وغير الربحية."
        />

        <section class="bg-white py-20 sm:py-24 dark:bg-ink-900">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-marketing.section-heading
                    title="ركائز الأمان في Veyra"
                    subtitle="ضمانات تقنية وتشغيلية يمكنك الاعتماد عليها عند تقييم المنصّة."
                />

                <div class="mt-16 grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($pillars as $pillar)
                        <div class="group rounded-2xl border border-mist-200 bg-ink-50/40 p-6 transition duration-200 ease-out hover:-translate-y-1 hover:border-emerald-400/50 hover:shadow-lg dark:border-ink-800 dark:bg-ink-800/60">
                            <div class="flex h-12 w-12 items-center justify-center rounded-xl bg-emerald-400/10 text-emerald-600 transition duration-200 group-hover:bg-emerald-400 group-hover:text-ink-950 dark:text-emerald-400">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $pillar['icon'] !!}</svg>
                            </div>
                            <h3 class="mt-5 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $pillar['title'] }}</h3>
                            <p class="mt-2 text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $pillar['description'] }}</p>
                        </div>
                    @endforeach
                </div>
            </div>
        </section>

        {{-- Lifecycle callout --}}
        <section class="bg-ink-100 py-20 sm:py-24 dark:bg-ink-950">
            <div class="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8">
                <x-marketing.section-heading
                    eyebrow="دورة حياة المستأجر"
                    title="خمس مراحل قبل منح الوصول الكامل"
                    subtitle="لا يُفتح النظام التشغيلي إلا بعد التحقق والموافقة — تحكم واضح في كل مرحلة."
                />

                <ol class="mx-auto mt-14 grid max-w-5xl gap-4 sm:grid-cols-5">
                    @foreach ([
                        'بانتظار التحقق',
                        'بانتظار الموافقة',
                        'نشط',
                        'موقوف',
                        'ملغى',
                    ] as $i => $state)
                        <li class="relative rounded-2xl border border-mist-200 bg-white p-4 text-center dark:border-ink-800 dark:bg-ink-800/60">
                            <span class="mx-auto flex h-8 w-8 items-center justify-center rounded-full bg-emerald-400/15 font-display text-sm font-bold text-emerald-600 dark:text-emerald-400">{{ $i + 1 }}</span>
                            <p class="mt-3 text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $state }}</p>
                        </li>
                    @endforeach
                </ol>
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
