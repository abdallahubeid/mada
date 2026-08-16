{{-- Full FAQ page — config/faq.php grouped by category. --}}
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

        <section class="border-b border-mist-200 bg-white py-6 dark:border-ink-800 dark:bg-ink-900">
            <div class="mx-auto flex max-w-3xl flex-wrap items-center justify-center gap-2 px-4 sm:px-6">
                @foreach ($categories as $category)
                    <a
                        href="#{{ $category['id'] }}"
                        class="rounded-md border border-mist-200 px-4 py-1.5 text-sm font-medium text-ink-600 transition duration-200 hover:border-brand-500 hover:text-brand-600 dark:border-ink-700 dark:text-mist-300 dark:hover:border-brand-500 dark:hover:text-brand-300"
                    >{{ $category['title'] }}</a>
                @endforeach
            </div>
        </section>

        <div class="bg-ink-100 py-16 dark:bg-ink-950">
            <div class="mx-auto max-w-3xl space-y-14 px-4 sm:px-6 lg:px-8">
                @foreach ($categories as $category)
                    <section id="{{ $category['id'] }}" class="scroll-mt-24">
                        <h2 class="font-display text-xl font-medium text-ink-900 dark:text-ink-50">{{ $category['title'] }}</h2>
                        <div class="mt-4">
                            <x-marketing.faq-accordion
                                :framed="false"
                                :items="$category['items']"
                            />
                        </div>
                    </section>
                @endforeach
            </div>
        </div>

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
