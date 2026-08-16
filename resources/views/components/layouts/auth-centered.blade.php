@props([
    'title' => null,
    'wide' => false,
])
<!DOCTYPE html>
{{--
    Centred authentication layout, in Odoo's shape: a thin brand header, one
    elevated card on a soft canvas, and a quiet footer. Nothing else.

    This replaces the split-screen layout, which paired a light form panel with
    a permanently dark decorative showcase. That showcase was the single worst
    offender for the "abrupt dark/light switching" the unified canvas is meant
    to remove — a visitor coming from a white landing page hit a half-black
    screen at the exact moment they were being asked to type a password.

    Hardcoded `ar`/`rtl` for the same reason components/layouts/guest.blade.php
    is (ADR-10 logical properties still govern, so this keeps working the moment
    a locale switcher exists).
--}}
<html lang="ar" dir="rtl" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'مدى' }}</title>

    <x-site-favicon />

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex h-full flex-col bg-mist-50 font-sans text-ink-900 antialiased">
    <header class="shrink-0 px-4 py-6 sm:px-6">
        <div class="mx-auto flex max-w-6xl items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="inline-flex items-baseline gap-2" aria-label="الصفحة الرئيسية — مدى">
                <span class="font-display text-2xl font-medium tracking-tight text-brand-600">مدى</span>
                <span class="text-xs font-medium text-mist-500">لإدارة المؤسسات</span>
            </a>

            <a href="{{ url('/') }}" class="inline-flex items-center gap-1.5 text-sm font-medium text-mist-500 transition duration-150 hover:text-brand-600">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" />
                </svg>
                العودة للموقع
            </a>
        </div>
    </header>

    {{--
        `flex-1` + centring rather than a fixed top margin: the register form is
        roughly twice the height of the login form, and a fixed offset either
        strands login near the top of a tall screen or pushes register off the
        bottom of a short one.
    --}}
    <main class="flex flex-1 items-center justify-center px-4 py-6 sm:px-6">
        {{--
            Both widths are written out in full. An interpolated
            `max-w-{{ $wide }}` would never reach the compiled stylesheet:
            Tailwind scans source text for complete class names and cannot see
            one that is assembled at render time.
        --}}
        <div @class(['w-full', 'max-w-xl' => $wide, 'max-w-md' => ! $wide])>
            {{ $slot }}
        </div>
    </main>

    <footer class="shrink-0 px-4 py-6 sm:px-6">
        <div class="mx-auto flex max-w-6xl flex-wrap items-center justify-center gap-x-5 gap-y-2 text-xs text-mist-500">
            <span>© {{ now()->year }} مدى</span>
            <a href="{{ route('marketing.privacy') }}" class="transition duration-150 hover:text-brand-600">سياسة الخصوصية</a>
            <a href="{{ route('marketing.terms') }}" class="transition duration-150 hover:text-brand-600">شروط الاستخدام</a>
            <a href="{{ route('marketing.contact') }}" class="transition duration-150 hover:text-brand-600">الدعم</a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
