@extends('tenant.portal.layout')

@section('title', $job['title'].' — '.$company['name'])

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 shadow-sm transition placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $errorClass = 'mt-1.5 text-xs text-danger-solid';
    @endphp

    <section class="relative overflow-hidden border-b border-mist-200 dark:border-ink-700">
        <div class="absolute inset-0 portal-grid-bg opacity-80"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-10 sm:px-6">
            <a href="{{ route('portal.careers', $slug) }}" class="text-sm font-bold text-brand-600 hover:text-brand-500 dark:text-brand-300">← العودة إلى الوظائف</a>
            <div class="mt-4">
                <span class="rounded-md bg-brand-500/10 px-2.5 py-0.5 text-xs font-bold text-brand-700 dark:text-brand-300">{{ $job['department'] }}</span>
                <h1 class="mt-3 font-display text-3xl font-medium text-ink-900 sm:text-4xl dark:text-ink-50">{{ $job['title'] }}</h1>
                <p class="mt-3 text-sm text-mist-500 dark:text-mist-400">
                    {{ $job['location'] }} · {{ $job['type'] }} · {{ $job['posted_at'] }}
                    @if (! empty($job['salary_range']))
                        · {{ $job['salary_range'] }}
                    @endif
                </p>
            </div>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="grid gap-6 lg:grid-cols-[1.15fr_0.85fr]">
            <article class="rounded-2xl border border-mist-200 bg-white p-6 shadow-sm dark:border-ink-600 dark:bg-ink-800 sm:p-8">
                <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">نبذة عن الدور</h2>
                <p class="mt-3 whitespace-pre-line text-sm leading-relaxed text-mist-500 dark:text-mist-400">{{ $job['description'] ?? $job['summary'] }}</p>

                @if (! empty($job['responsibilities']))
                    <h2 class="mt-8 font-display text-lg font-medium text-ink-900 dark:text-ink-50">المتطلبات / المسؤوليات</h2>
                    <ul class="mt-4 space-y-3">
                        @foreach ($job['responsibilities'] as $item)
                            <li class="flex gap-3 rounded-xl border border-mist-100 bg-mist-50/70 px-3 py-2 text-sm text-mist-600 dark:border-ink-700 dark:bg-ink-900/50 dark:text-mist-300">
                                <span class="mt-0.5 flex h-5 w-5 shrink-0 items-center justify-center rounded-md bg-brand-500 text-xs font-bold text-white"><svg xmlns="http://www.w3.org/2000/svg" class="h-3 w-3" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg></span>
                                <span>{{ $item }}</span>
                            </li>
                        @endforeach
                    </ul>
                @endif
            </article>

            <aside class="h-fit rounded-2xl border border-brand-500/20 bg-gradient-to-b from-white to-brand-50/40 p-6 shadow-lg dark:border-brand-500/20 dark:from-ink-800 dark:to-ink-900 sm:p-7">
                <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">قدّم على هذه الوظيفة</h2>
                <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">أرفق سيرتك الذاتية وسنتواصل معك عند المراجعة.</p>

                <form
                    method="POST"
                    action="{{ route('portal.jobs.apply', [$slug, $job['slug']]) }}"
                    enctype="multipart/form-data"
                    class="mt-6 space-y-4"
                >
                    @csrf
                    <div>
                        <label for="applicant_name" class="{{ $labelClass }}">الاسم الكامل</label>
                        <input id="applicant_name" name="applicant_name" type="text" required value="{{ old('applicant_name') }}" class="{{ $inputClass }}" placeholder="محمد أحمد">
                        @error('applicant_name')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="applicant_email" class="{{ $labelClass }}">البريد الإلكتروني</label>
                        <input id="email" name="email" type="email" required dir="ltr" value="{{ old('email') }}" class="{{ $inputClass }}" placeholder="you@email.com">
                        @error('email')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="applicant_phone" class="{{ $labelClass }}">رقم الجوال</label>
                        <input id="phone" name="phone" type="tel" required dir="ltr" value="{{ old('phone') }}" class="{{ $inputClass }}" placeholder="+9665xxxxxxxx">
                        @error('phone')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="cover_letter" class="{{ $labelClass }}">خطاب التغطية (اختياري)</label>
                        <textarea id="cover_letter" name="cover_letter" rows="3" class="{{ $inputClass }}">{{ old('cover_letter') }}</textarea>
                        @error('cover_letter')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                    <div>
                        <label for="cv" class="{{ $labelClass }}">السيرة الذاتية</label>
                        <input id="cv" name="cv" type="file" required accept=".pdf,.doc,.docx" class="{{ $inputClass }} file:me-3 file:rounded-md file:border-0 file:bg-brand-500/15 file:px-3 file:py-1.5 file:text-xs file:font-semibold file:text-brand-700 dark:file:text-brand-300">
                        @error('cv')
                            <p class="{{ $errorClass }}">{{ $message }}</p>
                        @enderror
                    </div>
                    <button type="submit" class="inline-flex w-full items-center justify-center rounded-xl bg-brand-500 px-4 py-3 text-sm font-bold text-white shadow-lg transition hover:bg-brand-600">
                        إرسال الطلب
                    </button>
                </form>
            </aside>
        </div>
    </section>
@endsection
