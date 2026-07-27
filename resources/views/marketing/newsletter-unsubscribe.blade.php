<x-layouts.marketing
    title="إلغاء الاشتراك — Veyra ERP"
    description="إلغاء الاشتراك من النشرة البريدية لـ Veyra ERP."
>
    <x-marketing.nav />

    <main>
        <section class="mx-auto flex min-h-[60vh] max-w-xl flex-col items-center justify-center px-4 py-20 text-center">
            <div class="rounded-2xl border border-mist-200 bg-white p-8 shadow-sm dark:border-ink-700 dark:bg-ink-900">
                @if ($found)
                    <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تم إلغاء الاشتراك</h1>
                    <p class="mt-3 text-sm leading-relaxed text-mist-500 dark:text-mist-400">
                        تم إلغاء اشتراك البريد
                        <span class="font-medium text-ink-700 dark:text-mist-200" dir="ltr">{{ $email }}</span>
                        من النشرة البريدية بنجاح.
                    </p>
                @else
                    <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">لم يتم العثور على الاشتراك</h1>
                    <p class="mt-3 text-sm leading-relaxed text-mist-500 dark:text-mist-400">
                        تعذّر العثور على اشتراك مرتبط بالبريد
                        <span class="font-medium text-ink-700 dark:text-mist-200" dir="ltr">{{ $email }}</span>.
                    </p>
                @endif

                <a href="{{ route('landing') }}" class="mt-6 inline-flex rounded-full bg-emerald-500 px-5 py-2.5 text-sm font-semibold text-ink-950 transition hover:bg-emerald-400">
                    العودة للرئيسية
                </a>
            </div>
        </section>
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
