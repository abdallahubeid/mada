{{-- Two-factor challenge for platform operators, in the public site's Andromeda identity (docs/DESIGN_SYSTEM.md §1.3). --}}
<x-layouts.auth-centered title="التحقق بخطوتين — مدى">
    <div
        x-data="{
            recovery: false,
            code: ['', '', '', '', '', ''],
            handleInput(i, e) {
                const val = e.target.value.replace(/\D/g, '').slice(-1);
                this.code[i] = val;
                e.target.value = val;
                if (val && i < 5) { e.target.nextElementSibling?.focus(); }
            },
            handleKeydown(i, e) {
                if (e.key === 'Backspace' && ! this.code[i] && i > 0) {
                    e.target.previousElementSibling?.focus();
                }
            },
            handlePaste(e) {
                const digits = (e.clipboardData.getData('text') || '').replace(/\D/g, '').slice(0, 6).split('');
                digits.forEach((d, idx) => { this.code[idx] = d; });
                e.preventDefault();
            },
        }"
        class="site-card p-6 sm:p-8"
    >
        {{-- Ambient brand glow --}}

        <div class="relative">
            {{-- Logo --}}
            <div class="flex justify-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-md bg-site-primary text-xl font-semibold text-white" aria-hidden="true">م</span>
            </div>

            <div class="mt-5 text-center">
                <h1 class="site-section-title site-h3">التحقق بخطوتين</h1>
                <p class="mt-8">أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة لإكمال تسجيل الدخول.</p>
            </div>

            <form action="#" method="POST" class="mt-7">
                @csrf

                {{-- OTP passcode inputs --}}
                <div x-show="!recovery" class="space-y-4">
                    <div class="flex justify-center gap-2 sm:gap-3" dir="ltr" @paste="handlePaste($event)">
                        @for ($i = 0; $i < 6; $i++)
                            <input
                                type="text"
                                inputmode="numeric"
                                maxlength="1"
                                autocomplete="one-time-code"
                                @input="handleInput({{ $i }}, $event)"
                                @keydown="handleKeydown({{ $i }}, $event)"
                                @if ($i === 0) x-init="$el.focus()" @endif
                                class="h-14 w-12 rounded border border-site-border bg-site-cream text-center text-2xl font-semibold text-site-dark transition focus:border-site-primary"
                                aria-label="الرقم {{ $i + 1 }} من 6"
                            >
                        @endfor
                    </div>
                </div>

                {{-- Recovery code fallback --}}
                <div x-show="recovery" x-cloak class="space-y-2">
                    <label for="recovery-code" class="site-label">رمز الاسترداد</label>
                    <input
                        type="text"
                        dir="ltr"
                        placeholder="xxxx-xxxx-xxxx"
                        id="recovery-code"
                        class="site-input text-center text-lg"
                    >
                </div>

                <button type="submit" class="site-btn site-btn-primary mt-6 w-full">
                    تأكيد الدخول
                </button>
            </form>

            {{-- Toggle between OTP and recovery --}}
            <div class="mt-5 text-center">
                <button type="button" x-show="!recovery" @click="recovery = true" class="site-link">
                    استخدام رمز الاسترداد
                </button>
                <button type="button" x-show="recovery" x-cloak @click="recovery = false" class="site-link">
                    العودة لرمز المصادقة
                </button>
            </div>

            <div class="mt-6 border-t border-site-border pt-5 text-center">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="transition hover:text-site-primary-ink hover:underline">
                        تسجيل الخروج من حساب آخر
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.auth-centered>
