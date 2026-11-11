{{--
    Request a password-reset link. Same card, fields and button as sign-in
    (Andromeda identity, docs/DESIGN_SYSTEM.md §1.3) — this screen previously
    still carried the withdrawn dark theme's card on the light canvas.
--}}
<x-layouts.auth-centered title="نسيت كلمة المرور — مدى">
    <div class="site-card p-6 sm:p-8">
        <div class="text-center">
            <h1 class="site-section-title site-h3">نسيت كلمة المرور؟</h1>
            <p class="mt-8">أدخل بريدك الإلكتروني وسنرسل لك رابطاً لإعادة تعيين كلمة المرور.</p>
        </div>

        @if (session('status'))
            <p class="site-notice mt-6" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('password.email') }}" class="mt-6 space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="site-label">البريد الإلكتروني</label>
                <input
                    id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus
                    placeholder="name@company.com"
                    dir="ltr"
                    class="site-input text-start"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                />
                @error('email') <p id="email-error" class="site-error">{{ $message }}</p> @enderror
            </div>

            <button type="submit" class="site-btn site-btn-primary w-full">إرسال رابط إعادة التعيين</button>
        </form>
    </div>

    <p class="mt-6 text-center">
        تذكرت كلمة المرور؟
        <a href="{{ route('login') }}" class="site-link">عودة لتسجيل الدخول</a>
    </p>
</x-layouts.auth-centered>
