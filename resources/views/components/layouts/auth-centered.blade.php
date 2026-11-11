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
<html lang="ar" dir="rtl" class="h-full">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'مدى' }}</title>

    <x-site-favicon />

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
{{--
    One viewport, no page scroll: the body is pinned to the dynamic viewport
    height (`h-dvh`, so mobile browser chrome showing or hiding never pushes
    the footer off), and header, panel and footer share it as a column.
    The screens are sized to fit at common desktop and phone viewports; when a
    viewport is genuinely shorter (a phone in landscape, 200% zoom) the panel
    area scrolls on its own instead of clipping the submit button.
--}}
<body class="site site-auth flex h-dvh flex-col overflow-hidden antialiased">
    <header class="shrink-0 py-2 sm:py-2.5 short:py-1">
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
        `my-auto` centring inside a scroll layer rather than `justify-center`:
        the register form is roughly twice the height of the login form, and
        when content is taller than the space, `justify-center` would push its
        top out of reach while `my-auto` simply lets it start at the top.
    --}}
    <main class="flex min-h-0 flex-1 flex-col">
        <div class="site-container-xl flex min-h-0 flex-1 flex-col">
            <div class="site-panel-host flex min-h-0 flex-1">
                <x-marketing.panel variant="flat" circles="single" />

                <div class="relative flex min-h-0 w-full flex-1 flex-col overflow-y-auto overscroll-contain px-4 py-3 sm:py-4 short:py-2 tiny:py-1.5">
                    {{--
                        Both widths are written out in full. An interpolated
                        `max-w-{{ $wide }}` would never reach the compiled stylesheet:
                        Tailwind scans source text for complete class names and cannot
                        see one that is assembled at render time.
                    --}}
                    <div @class(['mx-auto my-auto w-full', 'max-w-2xl' => $wide, 'max-w-md' => ! $wide])>
                        {{ $slot }}
                    </div>
                </div>
            </div>
        </div>
    </main>

    <footer class="shrink-0 py-2 text-sm sm:py-2.5 short:py-1 tiny:hidden">
        <div class="site-container-xl flex flex-wrap items-center justify-center gap-x-4 gap-y-1 sm:gap-x-6">
            {{-- The copyright drops on phones so the three links keep to one line. --}}
            <span class="hidden sm:inline">© {{ now()->year }} مدى</span>
            <a href="{{ route('marketing.privacy') }}" class="transition hover:text-site-primary-ink hover:underline">سياسة الخصوصية</a>
            <a href="{{ route('marketing.terms') }}" class="transition hover:text-site-primary-ink hover:underline">شروط الاستخدام</a>
            <a href="{{ route('marketing.contact') }}" class="transition hover:text-site-primary-ink hover:underline">الدعم</a>
        </div>
    </footer>

    @livewireScripts
</body>
</html>
