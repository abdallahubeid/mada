@props(['title' => null])
<!DOCTYPE html>
{{--
    Split-screen layout for the login page (Figma reference): a functional,
    light/dark-adaptive form panel (docs/DESIGN_SYSTEM.md §2, ADR-15) paired
    with a permanently dark, decorative brand showcase panel — the same
    "always-dark hero" treatment resources/views/landing.blade.php uses for
    its mock-dashboard visual, kept fixed regardless of theme for consistent
    brand punch. Hardcoded `ar`/`rtl` for the same reason
    components/layouts/guest.blade.php is (ADR-10 logical properties still
    govern, so this keeps working the moment a locale switcher exists).
--}}
<html lang="ar" dir="rtl" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Veyra ERP' }}</title>

    <x-site-favicon />

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="h-full font-sans antialiased">
    <div class="grid min-h-full lg:grid-cols-2">
        {{-- Form side — renders on the right in RTL since it's first in source order. --}}
        <div class="flex min-h-full items-center justify-center bg-ink-100 px-4 py-12 text-ink-600 dark:bg-ink-950 dark:text-ink-50">
            <div class="w-full max-w-md">
                {{ $slot }}
            </div>
        </div>

        {{-- Visual showcase side — decorative only, desktop-only, always dark. --}}
        {{ $visual ?? '' }}
    </div>

    @livewireScripts
</body>
</html>
