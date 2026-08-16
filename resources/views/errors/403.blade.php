<!DOCTYPE html>
{{--
    Brand-matched 403 Forbidden page — mirrors errors/404.blade.php (ink-900 canvas,
    glass illustration card, brand digit pill). Self-contained so it still
    renders when the app shell is unavailable. Always dark, matching other
    decorative brand surfaces (landing hero, login showcase, 404).
--}}
@php
    $homeUrl = route('landing');
    $homeLabel = 'العودة للرئيسية';

    if (auth()->check()) {
        $user = auth()->user();

        if ($user->canAccessPlatformConsole()) {
            $homeUrl = route($user->preferredAdminHomeRoute());
            $homeLabel = 'العودة للوحة التحكم';
        } elseif (\Illuminate\Support\Facades\Route::has('dashboard')) {
            $homeUrl = route('dashboard');
            $homeLabel = 'العودة للوحة التحكم';
        }
    }
@endphp
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>403 — غير مصرح بالوصول | مدى</title>
    <x-site-favicon />
    @vite(['resources/css/app.css'])
</head>
<body class="relative min-h-screen overflow-x-hidden bg-mist-50 px-4 py-10 font-sans text-mist-600 antialiased sm:py-14">
    {{-- Background Atmosphere --}}
    <div class="pointer-events-none absolute inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-24 start-1/4 h-[500px] w-[500px] rounded-md bg-brand-500/10 blur-[120px]"></div>
        <div class="absolute -bottom-24 -end-24 h-[600px] w-[600px] rounded-md bg-info-bg/5 blur-[150px]"></div>
    </div>

    <div class="relative z-10 mx-auto flex min-h-[calc(100vh-5rem)] w-full max-w-2xl flex-col items-center justify-center py-6 sm:min-h-[calc(100vh-7rem)]">
        {{-- Section - 403 View: illustration card + digit badge --}}
        <div class="relative mx-auto flex h-[14rem] w-[14rem] max-w-full shrink-0 items-center justify-center sm:h-[18rem] sm:w-[18rem]">
            <div class="absolute h-[11rem] w-[11rem] rounded-md bg-brand-500/20 blur-3xl sm:h-[14rem] sm:w-[14rem]"></div>

            <div class="relative flex h-full w-full items-center justify-center rounded-2xl bg-white p-6 shadow-lg backdrop-blur-md sm:p-8">
                <div class="relative flex h-full w-full items-center justify-center overflow-hidden rounded-3xl shadow-lg">
                    <div class="flex h-full w-full flex-col overflow-hidden rounded-3xl border border-mist-200 bg-mist-50">
                        <div class="flex items-center gap-1.5 border-b border-mist-200 px-4 py-3">
                            <span class="h-2 w-2 rounded-md bg-danger-solid"></span>
                            <span class="h-2 w-2 rounded-md bg-amber-400"></span>
                            <span class="h-2 w-2 rounded-md bg-brand-500"></span>
                        </div>
                        <div class="flex flex-1 flex-col items-center justify-center gap-4 px-6">
                            <span class="animate-glow-pulse flex h-16 w-16 items-center justify-center rounded-md border border-brand-500/30 text-brand-300">
                                <svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" class="h-9 w-9" aria-hidden="true">
                                    <rect x="5" y="11" width="14" height="10" rx="2" stroke="currentColor" stroke-width="1.4" />
                                    <path d="M8 11V8a4 4 0 0 1 8 0v3" stroke="currentColor" stroke-width="1.4" stroke-linecap="round" />
                                    <circle cx="12" cy="16" r="1.2" fill="currentColor" />
                                </svg>
                            </span>
                            <div class="text-center">
                                <p class="font-display text-xs font-medium uppercase tracking-[0.2em] text-brand-300">Access Denied</p>
                                <p class="mt-1.5 text-xs uppercase tracking-wider text-mist-500">Unauthorized Access Detected</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- "403" digit pill --}}
        <span class="relative z-10 -mt-6 inline-flex shrink-0 items-center justify-center rounded-md border border-mist-700 bg-mist-100 px-5 py-1.5 shadow-xl">
            <span class="font-display text-4xl font-medium tracking-[-0.96px] text-brand-300 drop-shadow-lg">
                403
            </span>
        </span>

        {{-- Heading + body copy --}}
        <div class="mt-6 shrink-0 space-y-3 text-center">
            <h1 class="font-display text-3xl font-medium leading-tight tracking-tight text-ink-900 sm:text-4xl">
                غير مصرح بالوصول
            </h1>
            <p class="mx-auto max-w-md text-base leading-relaxed text-mist-400">
                عفواً، ليس لديك صلاحية لعرض هذه الصفحة. إن كنت تعتقد أن هذا خطأ، تواصل مع مشرف المنصّة أو عد إلى لوحة التحكم.
            </p>
        </div>

        {{-- CTAs — kept above the fold; routing logic unchanged --}}
        <div class="mt-8 flex shrink-0 flex-col gap-4 sm:flex-row sm:justify-center">
            <a
                href="{{ $homeUrl }}"
                class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-6 py-2.5 text-base font-semibold text-white shadow-glow transition duration-200 ease-in-out hover:bg-brand-600 active:scale-[0.98]"
            >
                {{ $homeLabel }}
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true">
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
        <div class="relative z-10 mt-10 w-full shrink-0 border-t border-mist-700/40 pt-6 sm:mt-14 sm:pt-8">
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
