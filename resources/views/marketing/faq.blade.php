{{-- Full FAQ page — config/faq.php grouped by category, in the Andromeda accordion style. --}}
<x-layouts.marketing
    title="الأسئلة الشائعة — مدى"
    description="إجابات عن مدى: التسعير، الأمان، الإعداد، والدعم. قائمة كاملة مصنّفة حسب الموضوع."
>
    <x-marketing.nav />

    <main>
        <x-marketing.page-hero
            eyebrow="الأسئلة الشائعة"
            title="كل ما تحتاج معرفته"
            subtitle="إجابات مرتبة حسب الموضوع. لم تجد سؤالك؟ تواصل معنا مباشرة."
        />

        <section class="site-section">
            <div class="site-container max-w-4xl">
                <nav aria-label="أقسام الأسئلة">
                    <ul class="flex flex-wrap items-center justify-center gap-2">
                        @foreach ($categories as $category)
                            <li>
                                <a href="#{{ $category['id'] }}" class="site-btn site-btn-outline min-h-0 px-4 py-2">{{ $category['title'] }}</a>
                            </li>
                        @endforeach
                    </ul>
                </nav>

                <div class="mt-14 space-y-14">
                    @foreach ($categories as $category)
                        <section id="{{ $category['id'] }}" aria-labelledby="{{ $category['id'] }}-title">
                            <h2 id="{{ $category['id'] }}-title" class="site-section-title site-section-title--start site-h4">{{ $category['title'] }}</h2>
                            <div class="mt-10">
                                <x-marketing.faq-accordion :framed="false" :items="$category['items']" />
                            </div>
                        </section>
                    @endforeach
                </div>
            </div>
        </section>

        <x-marketing.cta-band
            title="لم تجد إجابتك؟"
            subtitle="فريق الدعم جاهز لمساعدتك."
            primary-label="تواصل معنا"
            primary-href="/contact"
            secondary-label="ابدأ التجربة المجانية"
            :secondary-href="route('register')"
        />
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
