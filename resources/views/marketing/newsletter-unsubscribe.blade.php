{{-- Newsletter unsubscribe confirmation, in the template's plain centred 404 layout. --}}
<x-layouts.marketing
    title="إلغاء الاشتراك — مدى"
    description="إلغاء الاشتراك من النشرة البريدية لـ مدى."
>
    <x-marketing.nav />

    <main>
        <section class="site-section">
            <div class="site-container">
                <div class="flex min-h-[40vh] items-center justify-center">
                    <div class="max-w-xl text-center">
                        @if ($found)
                            <h1 class="site-h2">تم إلغاء الاشتراك</h1>
                            <p class="mt-4">
                                تم إلغاء اشتراك البريد
                                <span class="font-medium text-site-dark" dir="ltr">{{ $email }}</span>
                                من النشرة البريدية بنجاح.
                            </p>
                        @else
                            <h1 class="site-h2">لم يتم العثور على الاشتراك</h1>
                            <p class="mt-4">
                                تعذّر العثور على اشتراك مرتبط بالبريد
                                <span class="font-medium text-site-dark" dir="ltr">{{ $email }}</span>.
                            </p>
                        @endif

                        <a href="{{ route('landing') }}" class="site-btn site-btn-primary mt-8">العودة للرئيسية</a>
                    </div>
                </div>
            </div>
        </section>
    </main>

    <x-marketing.footer />
</x-layouts.marketing>
