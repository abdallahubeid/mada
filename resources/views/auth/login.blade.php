@php
    /*
     * Field geometry is shared with register.blade.php and reset-password so the
     * three screens cannot drift. 40px controls — taller than the app's 32px
     * density, because an auth form is the one place a visitor types on a phone
     * with no keyboard shortcuts and no second chance at a typo.
     */
    $inputClasses = 'block h-10 w-full rounded-lg border border-mist-300 bg-white px-3 text-sm text-ink-900 transition duration-150 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/25';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-ink-700';
    $errorClasses = 'mt-1.5 text-xs font-medium text-critical-500';
    $primaryBtn = 'inline-flex h-11 w-full items-center justify-center gap-2 rounded-lg bg-brand-500 px-6 text-sm font-semibold text-white transition duration-150 ease-in-out hover:bg-brand-600 active:translate-y-px';
@endphp

<x-layouts.auth-centered title="تسجيل الدخول — مدى">
    <div class="mb-6 text-center">
        <h1 class="font-display text-3xl font-extrabold tracking-tight text-ink-900">أهلاً بعودتك</h1>
        <p class="mt-2 text-sm text-mist-500">سجّل الدخول للمتابعة إلى مساحة عملك.</p>
    </div>

    <div class="rounded-xl border border-mist-200 bg-white p-6 shadow-sm sm:p-8">
        @if (session('status'))
            <div class="mb-5 rounded-lg border border-success-500/25 bg-success-50 px-4 py-3 text-sm font-medium text-success-500">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="{{ $labelClasses }}">البريد الإلكتروني</label>
                <input
                    id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus
                    placeholder="name@company.com"
                    dir="ltr"
                    class="{{ $inputClasses }} text-start {{ $errors->has('email') ? '!border-critical-500' : '' }}"
                />
                @error('email') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="flex items-baseline justify-between gap-3">
                    <label for="password" class="{{ $labelClasses }}">كلمة المرور</label>
                    <a href="{{ route('password.request') }}" class="mb-1.5 text-xs font-medium text-brand-600 transition duration-150 hover:underline">نسيت كلمة المرور؟</a>
                </div>
                <input
                    id="password" name="password" type="password" autocomplete="current-password"
                    class="{{ $inputClasses }} {{ $errors->has('password') ? '!border-critical-500' : '' }}"
                />
                @error('password') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2.5 text-sm text-mist-600">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded-xs border-mist-300 text-brand-500 focus:ring-brand-500/40" />
                تذكّرني على هذا الجهاز
            </label>

            <button type="submit" class="{{ $primaryBtn }}">تسجيل الدخول</button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-mist-500">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="font-semibold text-brand-600 transition duration-150 hover:underline">أنشئ حساباً مجاناً</a>
    </p>
</x-layouts.auth-centered>
