@props([
    'title' => null,
    'wide' => false,
])
<!DOCTYPE html>
{{--
    Centred authentication layout, in the public site's Andromeda identity
    (docs/DESIGN_SYSTEM.md §1.3): a white bar with the brand, the template's
    cream wave panel with scattered circles behind one white card, and a quiet
    footer. A visitor arriving from the marketing site keeps the same canvas,
    type and accent at the moment they are asked for a password.

    `body.site` opts these screens into the `site-*` layer; nothing here reads
    the app's plum tokens.

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
<body class="site flex min-h-full flex-col antialiased">
    <header class="shrink-0 py-6">
        <div class="site-container-xl flex items-center justify-between gap-4">
            <a href="{{ url('/') }}" class="flex shrink-0 items-center gap-2.5" aria-label="الصفحة الرئيسية — مدى">
                @if ($logoUrl = \App\Models\Setting::assetUrl($settings['site_logo'] ?? null))
                    <img src="{{ $logoUrl }}" alt="مدى" class="h-10 max-h-10 w-auto max-w-[220px] shrink-0 object-contain object-start">
                @else
                    <span class="flex h-9 w-9 items-center justify-center rounded-md bg-site-primary text-lg font-semibold text-white" aria-hidden="true">م</span>
                    <span class="text-2xl font-semibold text-site-dark">مدى</span>
                @endif
            </a>

            <a href="{{ url('/') }}" class="site-nav-link inline-flex items-center gap-2">
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
    <main class="flex flex-1 flex-col">
        <div class="site-container-xl flex flex-1 flex-col">
            <div class="site-panel-host flex flex-1 items-center justify-center px-4 py-12 sm:py-16">
                <x-marketing.panel variant="flat" circles="single" />

                {{--
                    Both widths are written out in full. An interpolated
                    `max-w-{{ $wide }}` would never reach the compiled stylesheet:
                    Tailwind scans source text for complete class names and cannot
                    see one that is assembled at render time.
                --}}
                <div @class(['relative w-full', 'max-w-2xl' => $wide, 'max-w-md' => ! $wide])>
                    {{ $slot }}
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 py-6">
        <div class="site-container-xl flex flex-wrap items-center justify-center gap-x-6 gap-y-2">
            <span>© {{ now()->year }} مدى</span>
            <a href="{{ route('marketing.privacy') }}" class="transition hover:text-site-primary-ink hover:underline">سياسة الخصوصية</a>
            <a href="{{ route('marketing.terms') }}" class="transition hover:text-site-primary-ink hover:underline">شروط الاستخدام</a>
            <a href="{{ route('marketing.contact') }}" class="transition hover:text-site-primary-ink hover:underline">الدعم</a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
