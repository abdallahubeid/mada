{{--
    Choose a new password from an emailed reset link. Same card, fields and
    button as sign-in (Andromeda identity, docs/DESIGN_SYSTEM.md §1.3) — this
    screen previously still carried the withdrawn dark theme's card.
--}}
<x-layouts.auth-centered title="إعادة تعيين كلمة المرور — مدى">
    <div class="site-card p-6 sm:p-8">
        <div class="text-center">
            <h1 class="site-section-title site-h3">تعيين كلمة مرور جديدة</h1>
            <p class="mt-8">اختر كلمة مرور قوية لحسابك على مدى.</p>
        </div>

        <form method="POST" action="{{ route('password.update') }}" class="mt-6 space-y-5" novalidate>
            @csrf
            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="site-label">البريد الإلكتروني</label>
                <input
                    id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" autofocus
                    dir="ltr"
                    class="site-input text-start"
                    @error('email') aria-invalid="true" aria-describedby="email-error" @enderror
                />
                @error('email') <p id="email-error" class="site-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="site-label">كلمة المرور الجديدة</label>
                <input
                    id="password" name="password" type="password" autocomplete="new-password"
                    class="site-input"
                    @error('password') aria-invalid="true" aria-describedby="password-error" @enderror
                />
                @error('password') <p id="password-error" class="site-error">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="site-label">تأكيد كلمة المرور</label>
                <input
                    id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                    class="site-input"
                />
            </div>

            <button type="submit" class="site-btn site-btn-primary w-full">حفظ كلمة المرور</button>
        </form>
    </div>

    <p class="mt-6 text-center">
        <a href="{{ route('login') }}" class="site-link">عودة لتسجيل الدخول</a>
    </p>
</x-layouts.auth-centered>
