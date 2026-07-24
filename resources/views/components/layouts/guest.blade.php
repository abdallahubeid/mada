@props(['maxWidth' => 'max-w-md', 'title' => null])
<!DOCTYPE html>
{{--
    Registration, login, email-verification, and the pending-setup screen
    are all pre-full-access onboarding steps reached directly from the
    Arabic-first marketing funnel (resources/views/landing.blade.php), so
    this layout is hardcoded to `ar`/`rtl` for the same reason
    components/layouts/marketing.blade.php is — see that file's note.
--}}
<html lang="ar" dir="rtl" class="h-full scroll-smooth">
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
<body class="flex min-h-full items-center justify-center bg-ink-100 px-4 py-12 font-sans text-ink-600 dark:bg-ink-950 dark:text-ink-50">
    <div class="w-full {{ $maxWidth }}">
        {{ $slot }}
    </div>

    @livewireScripts
</body>
</html>
