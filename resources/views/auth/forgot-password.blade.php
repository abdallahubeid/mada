@php
    $inputClasses = 'block w-full rounded-xl border border-ink-500 bg-ink-800/80 px-3 py-2 text-sm text-ink-50 shadow-sm transition duration-150 placeholder:text-mist-500 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-mist-200';
    $errorClasses = 'mt-1.5 text-xs text-critical-500';
    $primaryBtn = 'inline-flex w-full items-center justify-center gap-2 rounded-md bg-brand-500 px-6 py-3 text-sm font-semibold text-white transition duration-200 ease-in-out hover:bg-brand-600 active:translate-y-px';
@endphp

<x-layouts.auth-centered title="نسيت كلمة المرور — مدى">
    <div class="mb-8 text-center">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="font-display text-2xl font-medium text-brand-300">مدى</span>
            <span class="text-sm text-mist-400">ERP</span>
        </a>
        <h1 class="mt-6 font-display text-2xl font-medium text-ink-50 sm:text-3xl">نسيت كلمة المرور؟</h1>
        <p class="mt-2 text-sm text-mist-400">أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.</p>
    </div>

    <div class="rounded-xl border border-ink-600/80 bg-ink-800/70 p-6 shadow-xl backdrop-blur-xl sm:p-8">
        @if (session('status'))
            <div class="mb-5 rounded-xl border border-brand-500/30 bg-brand-500/10 px-4 py-3 text-sm text-brand-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="{{ $labelClasses }}">البريد الإلكتروني</label>
                <input
                    id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus
                    class="{{ $inputClasses }} {{ $errors->has('email') ? '!border-critical-500' : '' }}"
                />
                @error('email') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="{{ $primaryBtn }}">إرسال رابط إعادة التعيين</button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-mist-400">
        تذكرت كلمة المرور؟
        <a href="{{ route('login') }}" class="font-semibold text-brand-300 hover:underline">عودة لتسجيل الدخول</a>
    </p>
</x-layouts.auth-centered>
