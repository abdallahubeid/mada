@php
    $inputClasses = 'block w-full rounded-xl border border-ink-500 bg-ink-800/80 px-4 py-2.5 text-sm text-ink-50 shadow-sm transition duration-150 placeholder:text-mist-500 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-mist-200';
    $errorClasses = 'mt-1.5 text-xs text-danger-solid';
    $primaryBtn = 'inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-500 px-6 py-3 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 ease-in-out hover:bg-emerald-400 active:scale-[0.98]';
@endphp

<x-layouts.guest title="إعادة تعيين كلمة المرور — Veyra ERP">
    <div class="mb-8 text-center">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="font-display text-2xl font-bold text-emerald-400">Veyra</span>
            <span class="text-sm text-mist-400">ERP</span>
        </a>
        <h1 class="mt-6 font-display text-2xl font-bold text-ink-50 sm:text-3xl">تعيين كلمة مرور جديدة</h1>
        <p class="mt-2 text-sm text-mist-400">اختر كلمة مرور قوية لحسابك على Veyra ERP.</p>
    </div>

    <div class="rounded-3xl border border-ink-600/80 bg-ink-800/70 p-6 shadow-xl backdrop-blur-xl sm:p-8">
        <form method="POST" action="{{ route('password.update') }}" class="space-y-5" novalidate>
            @csrf

            <input type="hidden" name="token" value="{{ $token }}">

            <div>
                <label for="email" class="{{ $labelClasses }}">البريد الإلكتروني</label>
                <input
                    id="email" name="email" type="email" value="{{ old('email', $email) }}" autocomplete="email" autofocus
                    class="{{ $inputClasses }} {{ $errors->has('email') ? '!border-danger-solid' : '' }}"
                />
                @error('email') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password" class="{{ $labelClasses }}">كلمة المرور الجديدة</label>
                <input
                    id="password" name="password" type="password" autocomplete="new-password"
                    class="{{ $inputClasses }} {{ $errors->has('password') ? '!border-danger-solid' : '' }}"
                />
                @error('password') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <label for="password_confirmation" class="{{ $labelClasses }}">تأكيد كلمة المرور</label>
                <input
                    id="password_confirmation" name="password_confirmation" type="password" autocomplete="new-password"
                    class="{{ $inputClasses }}"
                />
            </div>

            <button type="submit" class="{{ $primaryBtn }}">حفظ كلمة المرور</button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-mist-400">
        <a href="{{ route('login') }}" class="font-semibold text-emerald-400 hover:underline">عودة لتسجيل الدخول</a>
    </p>
</x-layouts.guest>
