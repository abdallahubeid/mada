@extends('tenant.portal.layout')

@section('title', 'تواصل معنا — '.$company['name'])

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $cards = [
            ['label' => 'هاتف المكتب', 'value' => $contact['phone'], 'dir' => 'ltr'],
            ['label' => 'بريد الدعم', 'value' => $contact['email'], 'dir' => 'ltr'],
            ['label' => 'العنوان', 'value' => $contact['address'], 'dir' => 'rtl'],
            ['label' => 'ساعات العمل', 'value' => $contact['hours'], 'dir' => 'rtl'],
        ];
    @endphp

    <section class="relative overflow-hidden border-b border-mist-200 dark:border-ink-700">
        <div class="absolute inset-0 portal-grid-bg"></div>
        <div class="pointer-events-none absolute -start-10 top-0 h-56 w-56 rounded-full bg-emerald-400/20 blur-3xl"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-14 sm:px-6 sm:py-20">
            <p class="text-xs font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">تواصل معنا</p>
            <h1 class="mt-3 max-w-3xl font-display text-3xl font-bold text-ink-900 sm:text-4xl dark:text-ink-50">
                نحن هنا للإجابة على جميع استفساراتك
            </h1>
            <p class="mt-4 max-w-2xl text-sm leading-relaxed text-mist-500 dark:text-mist-400 sm:text-base">
                تواصل مع فريق {{ $company['name'] }} بخصوص الفرص الوظيفية أو الشراكات أو أي سؤال عام — نرد خلال أيام العمل.
            </p>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ($cards as $card)
                <article class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm transition hover:-translate-y-0.5 hover:border-emerald-400/40 dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-xs font-bold text-emerald-600 dark:text-emerald-400">{{ $card['label'] }}</p>
                    <p class="mt-2 text-sm font-medium text-ink-800 dark:text-mist-200" dir="{{ $card['dir'] }}">{{ $card['value'] }}</p>
                </article>
            @endforeach
        </div>

        <div class="mt-8 grid gap-6 lg:grid-cols-2">
            <form
                id="portal-contact-form"
                method="POST"
                action="{{ route('portal.contact.store', $tenant->slug) }}"
                class="rounded-2xl border border-mist-200 bg-white p-6 shadow-sm dark:border-ink-600 dark:bg-ink-800 sm:p-8"
            >
                @csrf
                <h2 class="font-display text-xl font-semibold text-ink-900 dark:text-ink-50">أرسل رسالة</h2>
                <p class="mt-1 text-xs text-mist-500">نرد خلال أيام العمل — رسالتك تصل مباشرة لفريق {{ $company['name'] }}.</p>
                <div class="mt-6 space-y-4">
                    <div>
                        <label for="contact_name" class="{{ $labelClass }}">الاسم</label>
                        <input id="contact_name" name="name" type="text" required value="{{ old('name') }}" class="{{ $inputClass }}" placeholder="اسمك الكامل">
                        @error('name')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="contact_email" class="{{ $labelClass }}">البريد الإلكتروني</label>
                        <input id="contact_email" name="email" type="email" required dir="ltr" value="{{ old('email') }}" class="{{ $inputClass }}" placeholder="you@email.com">
                        @error('email')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="contact_subject" class="{{ $labelClass }}">الموضوع</label>
                        <input id="contact_subject" name="subject" type="text" required value="{{ old('subject') }}" class="{{ $inputClass }}" placeholder="مثال: استفسار عن وظيفة">
                        @error('subject')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="contact_message" class="{{ $labelClass }}">الرسالة</label>
                        <textarea id="contact_message" name="message" rows="5" required class="{{ $inputClass }}" placeholder="اكتب رسالتك هنا...">{{ old('message') }}</textarea>
                        @error('message')
                            <p class="mt-1 text-xs text-danger-solid">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-emerald-400 px-4 py-3 text-sm font-bold text-emerald-950 shadow-[0_0_28px_rgba(78,222,163,0.3)] transition hover:bg-emerald-300">
                        إرسال الرسالة
                    </button>
                </div>
            </form>

            <div class="overflow-hidden rounded-2xl border border-mist-200 bg-mist-50 shadow-sm dark:border-ink-600 dark:bg-ink-900">
                <div class="border-b border-mist-200 px-5 py-4 dark:border-ink-700">
                    <h2 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">موقع المقر الرئيسي</h2>
                    <p class="mt-1 text-xs text-mist-500">{{ $contact['address'] }}</p>
                </div>
                <div class="relative min-h-80 w-full bg-[linear-gradient(rgba(78,222,163,0.08)_1px,transparent_1px),linear-gradient(90deg,rgba(78,222,163,0.08)_1px,transparent_1px)] bg-[size:28px_28px]">
                    <iframe
                        title="خريطة المقر"
                        class="absolute inset-0 h-full w-full border-0 opacity-90 grayscale-[20%] contrast-110"
                        loading="lazy"
                        referrerpolicy="no-referrer-when-downgrade"
                        src="{{ $contact['map_embed_url'] }}"
                    ></iframe>
                    <div class="pointer-events-none absolute inset-x-0 bottom-0 bg-gradient-to-t from-ink-950/50 to-transparent p-4">
                        <p class="text-xs font-medium text-white">معاينة خريطة المقر — {{ $company['name'] }}</p>
                    </div>
                </div>
            </div>
        </div>
    </section>
@endsection
