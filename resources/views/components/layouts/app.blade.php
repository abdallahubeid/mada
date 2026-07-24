<!DOCTYPE html>
<html
    lang="{{ str_replace('_', '-', app()->getLocale()) }}"
    dir="{{ in_array(app()->getLocale(), ['ar', 'he', 'fa', 'ur']) ? 'rtl' : 'ltr' }}"
    class="h-full scroll-smooth"
>
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Veyra ERP' }}</title>

    {{-- Applied before first paint to avoid a flash of the wrong theme (docs/DESIGN_SYSTEM.md §2, ADR-15). --}}
    <script>
        (function () {
            const stored = localStorage.getItem('veyra-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', stored ? stored === 'dark' : prefersDark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body
    class="h-full bg-ink-100 font-sans text-ink-600 dark:bg-ink-950 dark:text-ink-50"
    x-data="{ sidebarOpen: false, notificationsOpen: false }"
>
    <div class="flex h-full">
        <x-layouts.partials.sidebar />

        <div class="flex min-w-0 flex-1 flex-col">
            <x-layouts.partials.topbar :title="$title ?? null" />

            <main class="flex-1 overflow-y-auto p-4 sm:p-6 lg:p-8">
                {{ $slot }}
            </main>
        </div>
    </div>

    <x-layouts.partials.notifications-drawer />

    @livewireScripts
</body>
</html>
