@extends('layouts.admin')

@section('title', 'أمان الحساب')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">الحساب والوصول</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">أمان الحساب</span>
@endsection

@section('content')
    @php
        $inputClass = 'w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50';
        $labelClass = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
        $cardClass = 'rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800';
    @endphp

    <div x-data="{ twoFaModal: false, recoveryDrawer: false }" class="mx-auto max-w-3xl space-y-6">
        {{-- Header --}}
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">أمان الحساب</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إدارة كلمة المرور والتحقق بخطوتين والجلسات النشطة لحسابك.</p>
        </div>

        {{-- Password update --}}
        <div class="{{ $cardClass }}">
            <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">تحديث كلمة المرور</h3>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">استخدم كلمة مرور طويلة وفريدة لحماية حساب المشرف.</p>

            <div class="mt-4 grid grid-cols-1 gap-4 sm:grid-cols-2">
                <div class="sm:col-span-2">
                    <label class="{{ $labelClass }}">كلمة المرور الحالية</label>
                    <input type="password" dir="ltr" class="{{ $inputClass }} max-w-md" autocomplete="current-password">
                </div>
                <div>
                    <label class="{{ $labelClass }}">كلمة المرور الجديدة</label>
                    <input type="password" dir="ltr" class="{{ $inputClass }}" autocomplete="new-password">
                </div>
                <div>
                    <label class="{{ $labelClass }}">تأكيد كلمة المرور</label>
                    <input type="password" dir="ltr" class="{{ $inputClass }}" autocomplete="new-password">
                </div>
            </div>

            <div class="mt-4 flex justify-end">
                <button type="button" class="rounded-xl bg-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">تحديث كلمة المرور</button>
            </div>
        </div>

        {{-- Two-Factor Authentication --}}
        <div class="{{ $cardClass }}">
            <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                <div class="min-w-0">
                    <div class="flex items-center gap-3">
                        <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">التحقق بخطوتين (2FA)</h3>
                        @if ($twoFactor['enabled'])
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-emerald-500/10 px-2.5 py-1 text-xs font-medium text-emerald-600 dark:text-emerald-400">
                                <span class="h-1.5 w-1.5 rounded-full bg-emerald-400"></span> مُفعّل
                            </span>
                        @else
                            <span class="inline-flex items-center gap-1.5 rounded-full bg-danger-solid/10 px-2.5 py-1 text-xs font-medium text-danger-solid">
                                <span class="h-1.5 w-1.5 rounded-full bg-danger-solid"></span> غير مُفعّل
                            </span>
                        @endif
                    </div>
                    <p class="mt-2 max-w-lg text-sm text-mist-500 dark:text-mist-400">
                        التحقق بخطوتين إلزامي لجميع مشرفي المنصّة (ADR-14). عند تسجيل الدخول ستحتاج إلى رمز من تطبيق المصادقة إضافةً إلى كلمة المرور.
                    </p>
                    @if ($twoFactor['enabled'])
                        <p class="mt-1 text-xs text-mist-400 dark:text-mist-500">تم التفعيل بتاريخ {{ $twoFactor['confirmed_at'] }}</p>
                    @endif
                </div>

                <div class="flex shrink-0 flex-wrap gap-2">
                    <button type="button" @click="recoveryDrawer = true" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 active:scale-95 dark:border-ink-600 dark:text-mist-200 dark:hover:text-emerald-400">
                        رموز الاسترداد
                    </button>
                    <button type="button" @click="twoFaModal = true" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">
                        {{ $twoFactor['enabled'] ? 'إعادة الضبط' : 'تفعيل الآن' }}
                    </button>
                </div>
            </div>
        </div>

        {{-- Active sessions --}}
        <div class="{{ $cardClass }} p-0">
            <div class="flex items-center justify-between p-5">
                <div>
                    <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">الجلسات النشطة</h3>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">الأجهزة المتصلة حاليًا بحسابك.</p>
                </div>
            </div>
            <div class="overflow-x-auto border-t border-mist-100 dark:border-ink-700">
                <table class="w-full min-w-max text-start text-sm">
                    <thead>
                        <tr class="text-xs uppercase tracking-wide text-mist-500 dark:text-mist-400">
                            <th class="px-5 py-3 text-start font-semibold">الجهاز</th>
                            <th class="px-5 py-3 text-start font-semibold">المتصفّح</th>
                            <th class="px-5 py-3 text-start font-semibold">الموقع</th>
                            <th class="px-5 py-3 text-start font-semibold">عنوان IP</th>
                            <th class="px-5 py-3 text-start font-semibold">آخر نشاط</th>
                            <th class="px-5 py-3 text-end font-semibold">الإجراء</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @foreach ($sessions as $session)
                            <tr class="transition duration-150 hover:bg-mist-50 dark:hover:bg-ink-700/40">
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="font-medium text-ink-900 dark:text-ink-50">{{ $session['device'] }}</span>
                                        @if ($session['current'])
                                            <span class="rounded-full bg-emerald-500/10 px-2 py-0.5 text-[10px] font-semibold text-emerald-600 dark:text-emerald-400">هذا الجهاز</span>
                                        @endif
                                    </div>
                                </td>
                                <td class="px-5 py-3.5 text-ink-700 dark:text-mist-200">{{ $session['browser'] }}</td>
                                <td class="px-5 py-3.5 text-ink-700 dark:text-mist-200">{{ $session['location'] }}</td>
                                <td class="px-5 py-3.5 font-mono text-xs text-mist-500 dark:text-mist-400">{{ $session['ip'] }}</td>
                                <td class="px-5 py-3.5 text-mist-500 dark:text-mist-400">{{ $session['last_active'] }}</td>
                                <td class="px-5 py-3.5 text-end">
                                    @if ($session['current'])
                                        <span class="text-xs text-mist-400 dark:text-mist-500">—</span>
                                    @else
                                        <button type="button" class="rounded-lg border border-danger-solid/30 px-3 py-1.5 text-xs font-semibold text-danger-solid transition hover:bg-danger-solid/10 active:scale-95">إنهاء الجلسة</button>
                                    @endif
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>
        </div>

        {{-- 2FA setup modal --}}
        <div
            x-show="twoFaModal"
            x-cloak
            x-transition:enter="transition ease-out duration-200"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-150"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
        >
            <div @click="twoFaModal = false" class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm"></div>
            <div
                x-show="twoFaModal"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                class="relative w-full max-w-md rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800"
            >
                <div class="flex items-center justify-between">
                    <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">إعداد التحقق بخطوتين</h3>
                    <button type="button" @click="twoFaModal = false" class="rounded-lg p-1 text-mist-400 transition hover:bg-mist-100 hover:text-mist-600 active:scale-90 dark:hover:bg-ink-700 dark:hover:text-white" aria-label="إغلاق">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">امسح رمز QR باستخدام تطبيق المصادقة (مثل Google Authenticator)، ثم أدخل الرمز المكوّن من 6 أرقام لتأكيد التفعيل.</p>

                {{-- Dummy QR code --}}
                <div class="mt-5 flex justify-center">
                    <div class="rounded-2xl border border-mist-200 bg-white p-3 dark:border-ink-600">
                        <div class="grid h-40 w-40 grid-cols-8 grid-rows-8 gap-0.5" aria-label="رمز QR للمصادقة">
                            @php $pattern = [1,1,1,0,1,0,1,1,1,0,0,1,1,0,1,0,1,0,1,1,0,1,1,1,0,1,1,0,1,0,0,1,1,0,0,1,0,1,1,0,0,1,1,0,1,1,0,1,1,1,0,1,0,0,1,0,1,0,1,1,1,0,1,1]; @endphp
                            @for ($i = 0; $i < 64; $i++)
                                <div class="{{ ($pattern[$i] ?? 0) ? 'bg-ink-950' : 'bg-transparent' }} rounded-[1px]"></div>
                            @endfor
                        </div>
                    </div>
                </div>

                <p class="mt-3 text-center text-xs text-mist-400 dark:text-mist-500">أو أدخل المفتاح يدويًا: <span class="font-mono text-mist-500 dark:text-mist-300" dir="ltr">JBSWY3DPEHPK3PXP</span></p>

                <div class="mt-5">
                    <label class="{{ $labelClass }}">رمز التحقق</label>
                    <input type="text" dir="ltr" maxlength="6" inputmode="numeric" placeholder="000000" class="{{ $inputClass }} text-center font-mono text-lg tracking-[0.5em]">
                </div>

                <div class="mt-5 flex justify-end gap-3">
                    <button type="button" @click="twoFaModal = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                    <button type="button" @click="twoFaModal = false" class="rounded-xl bg-emerald-400 px-5 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">تأكيد التفعيل</button>
                </div>
            </div>
        </div>

        {{-- Recovery codes drawer --}}
        <div
            x-show="recoveryDrawer"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="recoveryDrawer = false"
            class="fixed inset-0 z-50 bg-ink-950/60 backdrop-blur-sm"
        ></div>
        <aside
            x-show="recoveryDrawer"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full rtl:-translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full rtl:-translate-x-full opacity-0"
            class="fixed inset-y-0 end-0 z-50 flex w-full max-w-sm flex-col border-s border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800"
        >
            <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">رموز الاسترداد</h3>
                <button type="button" @click="recoveryDrawer = false" class="rounded-lg p-1 text-mist-400 transition hover:bg-mist-100 hover:text-mist-600 active:scale-90 dark:hover:bg-ink-700 dark:hover:text-white" aria-label="إغلاق">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>
            <div class="flex-1 space-y-4 overflow-y-auto p-5">
                <div class="flex items-start gap-3 rounded-xl border border-amber-500/30 bg-amber-500/10 p-3">
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-5 w-5 shrink-0 text-amber-500" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" /></svg>
                    <p class="text-xs text-amber-700 dark:text-amber-300">احتفظ بهذه الرموز في مكان آمن. يُستخدم كل رمز مرة واحدة فقط عند تعذّر الوصول إلى تطبيق المصادقة.</p>
                </div>

                <div class="grid grid-cols-2 gap-2">
                    @foreach ($recoveryCodes as $code)
                        <div class="rounded-lg border border-mist-200 bg-mist-50 px-3 py-2 text-center font-mono text-sm text-ink-700 dark:border-ink-600 dark:bg-ink-900 dark:text-mist-200" dir="ltr">{{ $code }}</div>
                    @endforeach
                </div>

                <div class="flex gap-2 pt-2">
                    <button type="button" class="flex-1 rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 active:scale-95 dark:border-ink-600 dark:text-mist-200 dark:hover:text-emerald-400">نسخ الكل</button>
                    <button type="button" class="flex-1 rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 active:scale-95 dark:border-ink-600 dark:text-mist-200 dark:hover:text-emerald-400">توليد رموز جديدة</button>
                </div>
            </div>
        </aside>
    </div>
@endsection
