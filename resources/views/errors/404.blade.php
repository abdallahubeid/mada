<!DOCTYPE html>
{{--
    Pixel-matched against the Figma "مدى - System Error Page" frame
    (node 2:1649): canvas #1a1620 (ink-900), a clean floating glass
    illustration card in #2c2734/60% (ink-700/60) with backdrop blur and no
    hard outer border, the "404" pill in ink-700 with a glowing brand
    digit stack, and two compact Cairo-weight CTAs. Self-contained (no x-layouts component,
    no Livewire directives) so it keeps rendering even if the app shell
    itself is broken — the whole point of an error page is to never
    itself error. Always dark regardless of the visitor's light/dark
    preference, matching the always-dark treatment used for decorative
    brand surfaces elsewhere (landing hero, login showcase panel).
--}}
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>404 — الصفحة غير موجودة | مدى</title>
    <x-site-favicon />
    @vite(['resources/css/app.css'])
</head>
<body class="relative flex min-h-screen items-center justify-center overflow-hidden bg-mist-50 px-4 py-16 font-sans text-mist-600 antialiased sm:py-20">
    {{-- Background Atmosphere --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-24 start-1/4 h-[500px] w-[500px] rounded-md bg-brand-500/10 blur-[120px]"></div>
        <div class="absolute -bottom-24 -end-24 h-[600px] w-[600px] rounded-md bg-info-bg/5 blur-[150px]"></div>
    </div>

    <div class="relative z-10 flex w-full max-w-2xl flex-col items-center">
        {{-- Section - 404 View: illustration card + digit badge --}}
        <div class="relative mx-auto flex h-[18rem] w-[18rem] max-w-full items-center justify-center sm:h-[22rem] sm:w-[22rem]">
            {{-- centered brand glow, directly behind the card --}}
            <div class="absolute h-[13rem] w-[13rem] rounded-md bg-brand-500/20 blur-3xl sm:h-[16rem] sm:w-[16rem]"></div>

            {{-- Clean floating glass card — no hard outer border, just a soft shadow + backdrop blur for depth. --}}
            <div class="relative flex h-full w-full items-center justify-center rounded-2xl bg-white p-8 shadow-lg backdrop-blur-md sm:p-10">
                <div class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-3xl shadow-lg">
                    {{-- Mini "system error" mockup, echoing the browser-chrome mockups used elsewhere (landing hero, login showcase). --}}
                    <div class="flex h-full w-full flex-col overflow-hidden rounded-3xl border border-mist-200 bg-mist-50">
                        <div class="flex items-center gap-1.5 border-b border-mist-200 px-4 py-3">
                            <span class="h-2 w-2 rounded-md bg-danger-solid"></span>
                            <span class="h-2 w-2 rounded-md bg-amber-400"></span>
                            <span class="h-2 w-2 rounded-md bg-brand-500"></span>
                        </div>
                        <div class="flex flex-1 flex-col items-center justify-center gap-4 px-6">
                            <span class="animate-glow-pulse flex h-16 w-16 items-center justify-center rounded-md border border-brand-500/30 text-brand-300">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-9 w-9">
                                    <circle cx="12" cy="12" r="9" stroke="currentColor" stroke-width="1" opacity="0.35" />
                                    <circle cx="12" cy="12" r="6" stroke="currentColor" stroke-width="1" opacity="0.6" />
                                    <circle cx="12" cy="12" r="1.4" fill="currentColor" />
                                    <path d="M12 2.5v3M12 18.5v3M2.5 12h3M18.5 12h3" stroke="currentColor" stroke-width="1.2" stroke-linecap="round" />
                                    <path d="M12 12 16.2 7.8" stroke="currentColor" stroke-width="1.5" stroke-linecap="round" />
                                </svg>
                            </span>
                            <div class="text-center">
                                <p class="font-display text-xs font-medium uppercase tracking-[0.2em] text-brand-300">System Error</p>
                                <p class="mt-1.5 text-xs uppercase tracking-wider text-mist-500">Unauthorized Access Detected</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- "404" digit pill --}}
        <span class="relative z-10 -mt-6 inline-flex items-center justify-center rounded-md border border-mist-700 bg-mist-100 px-5 py-1.5 shadow-xl">
            <span class="font-display text-4xl font-medium tracking-[-0.96px] text-brand-300 drop-shadow-lg">
                404
            </span>
        </span>

        {{-- Heading + body copy --}}
        <div class="mt-6 space-y-3 text-center">
            <h1 class="font-display text-3xl font-medium leading-tight tracking-tight text-ink-900 sm:text-4xl">
                الصفحة غير موجودة
            </h1>
            <p class="mx-auto max-w-md text-base leading-relaxed text-mist-400">
                عفواً، الصفحة التي تبحث عنها غير موجودة أو تم نقلها. تأكد من الرابط أو عد إلى الرئيسية.
            </p>
        </div>

        {{-- CTAs — primary renders first in DOM so it lands on the reading-start (right) side under RTL, matching the Figma layout. --}}
        <div class="mt-8 flex flex-col gap-4 sm:flex-row sm:justify-center">
            <a
                href="{{ route('landing') }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-base font-semibold text-white shadow-glow transition duration-200 ease-in-out hover:bg-brand-600 active:scale-[0.98]"
            >
                العودة للرئيسية
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M17.25 8.25 21 12m0 0-3.75 3.75M21 12H3" />
                </svg>
            </a>
            <button
                type="button"
                onclick="history.back()"
                class="inline-flex items-center justify-center gap-2 rounded-xl border border-mist-700 px-6 py-2.5 text-base font-semibold text-ink-900 transition duration-200 ease-in-out hover:border-brand-500 hover:text-brand-300 active:scale-[0.98]"
            >
                العودة للخلف
            </button>
        </div>

        {{-- Footer identity --}}
        <div class="relative z-10 mt-16 w-full border-t border-mist-700/40 pt-8 sm:mt-24">
            <div class="mx-auto flex max-w-xl flex-col items-center justify-between gap-4 text-sm font-medium text-mist-600/60 sm:flex-row">
                <div class="flex items-center gap-6">
                    <a href="{{ route('landing') }}#contact" class="transition hover:text-brand-300">سياسة الأمان</a>
                    <a href="{{ route('landing') }}#contact" class="transition hover:text-brand-300">الدعم الفني</a>
                </div>
                <p>© {{ now()->year }} مدى. جميع الحقوق محفوظة.</p>
                <p class="font-display text-lg font-medium text-brand-300">مدى</p>
            </div>
        </div>
    </div>
</body>
</html>
