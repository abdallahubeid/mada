{{--
    Sign-in, in the public site's Andromeda identity: the template's form card
    (white, faint shadow) on the cream wave panel, cream inputs with the
    orange edge, and one full-width primary button.

    Field geometry comes from the shared `site-input` class, so this screen,
    register and the password screens cannot drift apart.
--}}
<x-layouts.auth-centered title="تسجيل الدخول — مدى">
    <div class="site-card p-5 sm:p-7">
        <div class="text-center">
            {{-- mb-7 reserves the underline bar, whether or not the subtitle shows. --}}
            <h1 class="site-section-title site-h4 mb-7">أهلاً بعودتك</h1>
            <p class="short:hidden">سجّل الدخول للمتابعة إلى مساحة عملك.</p>
        </div>

        @if (session('status'))
            <p class="site-notice mt-4" role="status">{{ session('status') }}</p>
        @endif

        <form method="POST" action="{{ route('login') }}" class="mt-5 space-y-3.5" novalidate>
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

            <div>
                <div class="flex items-baseline justify-between gap-3">
                    <label for="password" class="site-label">كلمة المرور</label>
                    <a href="{{ route('password.request') }}" class="site-link mb-1.5">نسيت كلمة المرور؟</a>
                </div>
                <input
                    id="password" name="password" type="password" autocomplete="current-password"
                    class="site-input"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                />
                @error('password') <p id="password-error" class="site-error">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2.5">
                <input type="checkbox" name="remember" value="1" class="site-check" />
                تذكّرني على هذا الجهاز
            </label>

            <button type="submit" class="site-btn site-btn-primary w-full">تسجيل الدخول</button>
        </form>
    </div>

    <p class="mt-3 text-center">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="site-link">أنشئ حساباً مجاناً</a>
    </p>
</x-layouts.auth-centered>
