<!DOCTYPE html>
<html lang="ar" dir="rtl" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', ($company['name'] ?? 'المؤسسة').' — بوابة التوظيف')</title>
    <x-site-favicon />
    <x-theme-script />
    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <style>
        .portal-grid-bg {
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(78, 222, 163, 0.22), transparent 55%),
                radial-gradient(ellipse 60% 40% at 100% 0%, rgba(78, 222, 163, 0.08), transparent 45%),
                linear-gradient(rgba(148, 163, 184, 0.08) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.08) 1px, transparent 1px);
            background-size: auto, auto, 48px 48px, 48px 48px;
        }
        .dark .portal-grid-bg {
            background-image:
                radial-gradient(ellipse 80% 50% at 50% -20%, rgba(78, 222, 163, 0.14), transparent 55%),
                radial-gradient(ellipse 50% 35% at 90% 10%, rgba(78, 222, 163, 0.06), transparent 45%),
                linear-gradient(rgba(148, 163, 184, 0.06) 1px, transparent 1px),
                linear-gradient(90deg, rgba(148, 163, 184, 0.06) 1px, transparent 1px);
            background-size: auto, auto, 48px 48px, 48px 48px;
        }
        .portal-glass {
            background: rgba(255, 255, 255, 0.72);
            backdrop-filter: blur(14px);
        }
        .dark .portal-glass {
            background: rgba(15, 23, 42, 0.55);
        }
    </style>
</head>
<body
    class="min-h-full bg-neutral-50 font-sans text-ink-600 antialiased dark:bg-ink-950 dark:text-mist-300"
    x-data="{ navOpen: false }"
>
    <header class="sticky top-0 z-50 border-b border-mist-200/60 bg-white/80 backdrop-blur-xl dark:border-ink-700/60 dark:bg-ink-950/75">
        <div class="mx-auto flex h-16 max-w-6xl items-center justify-between gap-3 px-4 sm:px-6">
            <a href="{{ route('portal.index', $slug) }}" class="flex min-w-0 items-center gap-3">
                <span class="relative flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-emerald-400 font-display text-lg font-bold text-emerald-900 shadow-[0_0_24px_rgba(78,222,163,0.45)]">
                    {{ $company['logo_initial'] }}
                </span>
                <span class="min-w-0">
                    <span class="block truncate font-display text-sm font-bold text-ink-900 dark:text-ink-50">{{ $company['name'] }}</span>
                    <span class="block text-[11px] text-mist-500">بوابة التوظيف</span>
                </span>
            </a>

            <nav class="hidden items-center gap-0.5 lg:flex">
                @php
                    $nav = array_values(array_filter([
                        ['label' => 'الرئيسية', 'href' => route('portal.index', $slug), 'active' => request()->routeIs('portal.index')],
                        $portalSettings->isSectionActive('about') ? ['label' => 'من نحن', 'href' => route('portal.index', $slug).'#about', 'active' => false] : null,
                        $portalSettings->isSectionActive('services') ? ['label' => 'خدماتنا', 'href' => route('portal.index', $slug).'#services', 'active' => false] : null,
                        $portalSettings->isSectionActive('culture') ? ['label' => 'بيئة العمل', 'href' => route('portal.index', $slug).'#culture', 'active' => false] : null,
                        $portalSettings->isSectionActive('careers') ? ['label' => 'الوظائف', 'href' => route('portal.careers', $slug), 'active' => request()->routeIs('portal.careers', 'portal.jobs.show')] : null,
                        $portalSettings->isSectionActive('contact') ? ['label' => 'تواصل معنا', 'href' => route('portal.contact', $slug), 'active' => request()->routeIs('portal.contact')] : null,
                    ]));
                @endphp
                @foreach ($nav as $item)
                    <a
                        href="{{ $item['href'] }}"
                        @class([
                            'rounded-lg px-3 py-2 text-sm font-medium transition',
                            'bg-emerald-400/10 text-emerald-600 dark:text-emerald-400' => $item['active'],
                            'text-mist-500 hover:bg-mist-100 hover:text-ink-700 dark:hover:bg-ink-800 dark:hover:text-mist-100' => ! $item['active'],
                        ])
                    >{{ $item['label'] }}</a>
                @endforeach
            </nav>

            <div class="flex items-center gap-2">
                <button
                    type="button"
                    onclick="(function(){const root=document.documentElement;const nextDark=!root.classList.contains('dark');root.classList.toggle('dark',nextDark);localStorage.setItem('veyra-theme',nextDark?'dark':'light');})()"
                    class="rounded-xl border border-mist-200 p-2 text-mist-500 transition hover:border-emerald-400/50 hover:text-emerald-600 dark:border-ink-600 dark:hover:border-emerald-400/40 dark:hover:text-emerald-400"
                    aria-label="تبديل الوضع الليلي"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
                    </svg>
                </button>
                @if ($portalSettings->isSectionActive('careers'))
                    <a
                        href="{{ route('portal.careers', $slug) }}"
                        class="hidden rounded-xl bg-emerald-400 px-4 py-2 text-sm font-bold text-emerald-950 shadow-[0_0_28px_rgba(78,222,163,0.35)] transition hover:bg-emerald-300 sm:inline-flex"
                    >
                        الشواغر المتاحة
                    </a>
                @endif
                <button
                    type="button"
                    class="rounded-xl border border-mist-200 p-2 text-mist-500 lg:hidden dark:border-ink-600"
                    @click="navOpen = ! navOpen"
                    aria-label="فتح القائمة"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
                    </svg>
                </button>
            </div>
        </div>

        <div x-show="navOpen" x-cloak x-transition class="border-t border-mist-200 bg-white px-4 py-3 lg:hidden dark:border-ink-700 dark:bg-ink-900">
            <div class="flex flex-col gap-1">
                @foreach ($nav as $item)
                    <a href="{{ $item['href'] }}" @click="navOpen = false" class="rounded-lg px-3 py-2.5 text-sm font-medium text-ink-700 hover:bg-mist-100 dark:text-mist-200 dark:hover:bg-ink-800">{{ $item['label'] }}</a>
                @endforeach
                @if ($portalSettings->isSectionActive('careers'))
                    <a href="{{ route('portal.careers', $slug) }}" class="mt-2 rounded-xl bg-emerald-400 px-4 py-2.5 text-center text-sm font-bold text-emerald-950">الشواغر المتاحة</a>
                @endif
            </div>
        </div>
    </header>

    <main>
        @yield('content')
    </main>

    <footer class="relative mt-20 overflow-hidden border-t border-mist-200 bg-ink-950 text-mist-300">
        <div class="pointer-events-none absolute inset-0 opacity-40 portal-grid-bg"></div>
        <div class="relative mx-auto grid max-w-6xl gap-10 px-4 py-12 sm:px-6 md:grid-cols-3">
            <div>
                <div class="flex items-center gap-3">
                    <span class="flex h-10 w-10 items-center justify-center rounded-xl bg-emerald-400 font-display text-lg font-bold text-emerald-900">{{ $company['logo_initial'] }}</span>
                    <div>
                        <p class="font-display text-sm font-bold text-white">{{ $company['name'] }}</p>
                        <p class="text-xs text-mist-500">بوابة التوظيف الرسمية</p>
                    </div>
                </div>
                <p class="mt-4 text-sm leading-relaxed text-mist-400">{{ $company['tagline'] }}</p>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-wide text-mist-500 uppercase">التنقّل</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li><a href="{{ route('portal.index', $slug) }}" class="transition hover:text-emerald-400">الرئيسية</a></li>
                    <li><a href="{{ route('portal.careers', $slug) }}" class="transition hover:text-emerald-400">الوظائف</a></li>
                    <li><a href="{{ route('portal.contact', $slug) }}" class="transition hover:text-emerald-400">تواصل معنا</a></li>
                </ul>
            </div>
            <div>
                <p class="text-xs font-semibold tracking-wide text-mist-500 uppercase">التواصل</p>
                <ul class="mt-3 space-y-2 text-sm">
                    <li dir="ltr" class="text-start">{{ $contact['email'] ?? '' }}</li>
                    <li dir="ltr" class="text-start">{{ $contact['phone'] ?? '' }}</li>
                    <li>{{ $contact['address'] ?? '' }}</li>
                </ul>
            </div>
        </div>
        <div class="relative border-t border-white/10">
            <div class="mx-auto flex max-w-6xl flex-col gap-2 px-4 py-4 text-xs text-mist-500 sm:flex-row sm:items-center sm:justify-between sm:px-6">
                <p>© {{ date('Y') }} {{ $company['name'] }}. جميع الحقوق محفوظة.</p>
                <p>مدعوم بواسطة <span class="text-emerald-400">Veyra ERP</span></p>
            </div>
        </div>
    </footer>

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('flasher'))
        <script>
            document.addEventListener('DOMContentLoaded', () => {
                const flasher = @js(session('flasher'));
                if (window.Swal && flasher?.message) {
                    Swal.fire({
                        icon: flasher.type || 'success',
                        title: flasher.message,
                        confirmButtonColor: '#4edea3',
                        confirmButtonText: 'حسنًا',
                    });
                }
            });
        </script>
    @endif
    @stack('scripts')
</body>
</html>
