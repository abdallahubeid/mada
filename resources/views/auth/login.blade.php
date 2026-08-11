@php
    $inputClasses = 'block w-full rounded-xl border border-mist-300 bg-white px-4 py-2.5 text-sm text-ink-900 shadow-sm transition duration-150 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-500 dark:bg-ink-600 dark:text-ink-50 dark:placeholder:text-mist-500';
    $labelClasses = 'mb-1.5 block text-sm font-medium text-ink-700 dark:text-mist-200';
    $errorClasses = 'mt-1.5 text-xs text-danger-solid';
    $primaryBtn = 'inline-flex w-full items-center justify-center gap-2 rounded-full bg-emerald-500 px-6 py-3 text-sm font-semibold text-ink-950 shadow-glow transition duration-200 ease-in-out hover:bg-emerald-400 active:scale-[0.98]';
@endphp

<x-layouts.auth-split title="تسجيل الدخول — Veyra ERP">
    <div class="mb-8 text-center">
        <a href="/" class="inline-flex items-center gap-2">
            <span class="font-display text-2xl font-bold text-emerald-600 dark:text-emerald-400">Veyra</span>
            <span class="text-sm text-mist-500">ERP</span>
        </a>
        <h1 class="mt-6 font-display text-2xl font-bold text-ink-900 dark:text-ink-50 sm:text-3xl">مرحباً بعودتك</h1>
        <p class="mt-2 text-sm text-mist-500 dark:text-mist-400">سجّل الدخول لمتابعة إدارة مؤسستك على Veyra ERP.</p>
    </div>

    <div class="rounded-3xl border border-mist-200 bg-white p-6 shadow-sm backdrop-blur-xl dark:border-ink-600 dark:bg-ink-700/90 sm:p-8">
        @if (session('status'))
            <div class="mb-5 rounded-xl border border-emerald-400/30 bg-emerald-400/10 px-4 py-3 text-sm text-emerald-700 dark:text-emerald-300">
                {{ session('status') }}
            </div>
        @endif

        <form method="POST" action="{{ route('login') }}" class="space-y-5" novalidate>
            @csrf

            <div>
                <label for="email" class="{{ $labelClasses }}">البريد الإلكتروني</label>
                <input
                    id="email" name="email" type="email" value="{{ old('email') }}" autocomplete="email" autofocus
                    class="{{ $inputClasses }} {{ $errors->has('email') ? '!border-danger-solid' : '' }}"
                />
                @error('email') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
            </div>

            <div>
                <div class="flex items-center justify-between">
                    <label for="password" class="{{ $labelClasses }}">كلمة المرور</label>
                    <a href="{{ route('password.request') }}" class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">نسيت كلمة المرور؟</a>
                </div>
                <input
                    id="password" name="password" type="password" autocomplete="current-password"
                    class="{{ $inputClasses }} {{ $errors->has('password') ? '!border-danger-solid' : '' }}"
                />
                @error('password') <p class="{{ $errorClasses }}">{{ $message }}</p> @enderror
            </div>

            <label class="flex items-center gap-2.5 text-sm text-mist-600 dark:text-mist-300">
                <input type="checkbox" name="remember" value="1" class="h-4 w-4 rounded border-mist-300 text-emerald-500 focus:ring-emerald-400/40 dark:border-ink-500 dark:bg-ink-600" />
                تذكرني على هذا الجهاز
            </label>

            <button type="submit" class="{{ $primaryBtn }}">تسجيل الدخول</button>
        </form>
    </div>

    <p class="mt-6 text-center text-sm text-mist-500 dark:text-mist-400">
        ليس لديك حساب؟
        <a href="{{ route('register') }}" class="font-semibold text-emerald-600 hover:underline dark:text-emerald-400">ابدأ تجربتك المجانية</a>
    </p>

    <x-slot:visual>
        <div class="relative hidden overflow-hidden bg-ink-950 lg:flex lg:flex-col lg:items-center lg:justify-center lg:px-16 lg:py-12">
            <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
                <div class="absolute -top-32 start-1/3 h-96 w-96 rounded-full bg-emerald-400/20 blur-3xl animate-glow-pulse"></div>
                <div class="absolute bottom-0 end-0 h-80 w-80 translate-x-1/4 rounded-full bg-emerald-500/15 blur-3xl"></div>
            </div>

            <div class="relative z-10 max-w-md text-center">
                <span class="inline-flex items-center gap-2 rounded-full border border-emerald-400/30 bg-emerald-400/10 px-4 py-1.5 text-xs font-semibold text-emerald-400">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M11.48 3.499a.562.562 0 0 1 1.04 0l2.125 5.111a.563.563 0 0 0 .475.345l5.518.442c.5.04.703.662.321.988l-4.204 3.602a.563.563 0 0 0-.182.557l1.285 5.385a.562.562 0 0 1-.84.61l-4.725-2.885a.562.562 0 0 0-.586 0L6.982 20.54a.562.562 0 0 1-.84-.61l1.285-5.386a.562.562 0 0 0-.182-.557l-4.204-3.602a.562.562 0 0 1 .321-.988l5.518-.442a.563.563 0 0 0 .475-.345l2.125-5.111Z" /></svg>
                    منصة SaaS متكاملة لإدارة المؤسسات
                </span>

                <h2 class="mt-6 font-display text-3xl font-bold leading-tight text-ink-50">
                    كل أدوات مؤسستك، في مكان واحد أنيق
                </h2>
                <p class="mt-4 text-sm leading-relaxed text-mist-400">
                    {{-- "المشاريع" (module does not exist) and "أمان تام / عزل كامل"
                         (ADR-02 is row-level, not database-per-tenant) removed 2026-08-10. --}}
                    التوظيف، الموارد البشرية، والرواتب — بعزل بيانات على مستوى الصف وسجل تدقيق لكل مؤسسة.
                </p>
            </div>

            {{-- Glassmorphic showcase card --}}
            <div class="relative z-10 mt-12 w-full max-w-sm">
                <div class="rounded-3xl border border-ink-700 bg-ink-800/60 p-2 shadow-2xl backdrop-blur-xl">
                    <div class="overflow-hidden rounded-2xl bg-ink-900">
                        <div class="flex items-center gap-2 border-b border-ink-700 px-4 py-3">
                            <span class="h-2.5 w-2.5 rounded-full bg-danger-solid"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-amber-400"></span>
                            <span class="h-2.5 w-2.5 rounded-full bg-emerald-400"></span>
                            <span class="ms-3 text-xs text-mist-500">app.veyra.com</span>
                        </div>
                        <div class="grid grid-cols-3 gap-3 p-5">
                            <div class="col-span-2 rounded-xl bg-ink-800 p-4">
                                <p class="text-xs text-mist-400">الأداء الشهري</p>
                                <div class="mt-3 flex h-20 items-end gap-1.5">
                                    <span class="h-[35%] w-full rounded-t-md bg-emerald-400/30"></span>
                                    <span class="h-[55%] w-full rounded-t-md bg-emerald-400/40"></span>
                                    <span class="h-[40%] w-full rounded-t-md bg-emerald-400/30"></span>
                                    <span class="h-[75%] w-full rounded-t-md bg-emerald-400/60"></span>
                                    <span class="h-full w-full rounded-t-md bg-emerald-400"></span>
                                </div>
                            </div>
                            <div class="space-y-2.5">
                                <div class="rounded-xl bg-ink-800 p-2.5 text-center">
                                    <p class="text-[10px] text-mist-400">الفرق</p>
                                    <p class="mt-1 font-display text-sm font-bold text-ink-50">12</p>
                                </div>
                                <div class="rounded-xl bg-ink-800 p-2.5 text-center">
                                    <p class="text-[10px] text-mist-400">المهام</p>
                                    <p class="mt-1 font-display text-sm font-bold text-emerald-400">86%</p>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                {{-- Floating metric pills — subtle, slow, and desynced via animation-delay. --}}
                <div class="animate-float-slow absolute -top-6 -start-8 flex items-center gap-2 rounded-2xl border border-ink-700 bg-ink-800/90 px-4 py-2.5 shadow-glow backdrop-blur-xl">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/15 text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6.03 11.959 11.959 0 0 0 3 9.75c0 5.592 3.824 10.29 9 11.622 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.72A11.959 11.959 0 0 1 12 2.714Z" />
                        </svg>
                    </span>
                    <p class="whitespace-nowrap text-xs font-semibold text-ink-50">عزل بيانات 100%</p>
                </div>

                <div class="animate-float-slower absolute -bottom-6 -end-6 flex items-center gap-2 rounded-2xl border border-ink-700 bg-ink-800/90 px-4 py-2.5 shadow-glow backdrop-blur-xl [animation-delay:-3s]">
                    <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-emerald-400/15 text-emerald-400">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.813 15.904 9 18.75l-.813-2.846a4.5 4.5 0 0 0-3.09-3.09L2.25 12l2.846-.813a4.5 4.5 0 0 0 3.09-3.09L9 5.25l.813 2.846a4.5 4.5 0 0 0 3.09 3.09l2.846.813-2.846.813a4.5 4.5 0 0 0-3.09 3.09Z" />
                        </svg>
                    </span>
                    <p class="whitespace-nowrap text-xs font-semibold text-ink-50">أداء لحظي</p>
                </div>
            </div>
        </div>
    </x-slot:visual>
</x-layouts.auth-split>
