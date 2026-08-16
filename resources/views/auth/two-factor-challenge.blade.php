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
        class="relative overflow-hidden rounded-xl border border-mist-200 bg-white p-8 shadow-xl"
    >
        {{-- Ambient brand glow --}}
        <div class="pointer-events-none absolute -top-16 start-1/2 h-40 w-40 -translate-x-1/2 rounded-md bg-brand-500/20 blur-3xl rtl:translate-x-1/2"></div>

        <div class="relative">
            {{-- Logo --}}
            <div class="flex justify-center">
                <span class="flex h-12 w-12 items-center justify-center rounded-2xl bg-brand-500 font-display text-xl font-medium text-white">م</span>
            </div>

            <div class="mt-5 text-center">
                <h1 class="font-display text-2xl font-medium text-ink-900">التحقق بخطوتين</h1>
                <p class="mt-2 text-sm text-mist-500">أدخل الرمز المكوّن من 6 أرقام من تطبيق المصادقة لإكمال تسجيل الدخول.</p>
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
                                class="h-14 w-12 rounded-xl border border-mist-200 bg-white text-center font-mono text-2xl font-bold text-ink-900 transition focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                            >
                        @endfor
                    </div>
                </div>

                {{-- Recovery code fallback --}}
                <div x-show="recovery" x-cloak class="space-y-2">
                    <label class="mb-1.5 block text-sm font-medium text-ink-700">رمز الاسترداد</label>
                    <input
                        type="text"
                        dir="ltr"
                        placeholder="xxxx-xxxx-xxxx"
                        class="w-full rounded-xl border border-mist-200 bg-white px-3 py-3 text-center font-mono text-lg text-ink-900 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30"
                    >
                </div>

                <button type="submit" class="mt-6 w-full rounded-xl bg-brand-500 px-5 py-3 text-sm font-semibold text-white transition duration-200 hover:bg-brand-600 active:translate-y-px">
                    تأكيد الدخول
                </button>
            </form>

            {{-- Toggle between OTP and recovery --}}
            <div class="mt-5 text-center">
                <button type="button" x-show="!recovery" @click="recovery = true" class="text-sm font-medium text-brand-600 transition hover:text-brand-500">
                    استخدام رمز الاسترداد
                </button>
                <button type="button" x-show="recovery" x-cloak @click="recovery = false" class="text-sm font-medium text-brand-600 transition hover:text-brand-500">
                    العودة لرمز المصادقة
                </button>
            </div>

            <div class="mt-6 border-t border-mist-100 pt-5 text-center">
                <form action="{{ route('logout') }}" method="POST">
                    @csrf
                    <button type="submit" class="text-xs font-medium text-mist-400 transition hover:text-mist-600">
                        تسجيل الخروج من حساب آخر
                    </button>
                </form>
            </div>
        </div>
    </div>
</x-layouts.auth-centered>
