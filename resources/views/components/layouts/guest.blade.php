@props(['maxWidth' => 'max-w-md', 'title' => null])
<!DOCTYPE html>
{{--
    Centered auth card layout for forgot/reset password, email verification,
    and other non-split guest flows. Full-viewport navy background — no 50/50 grid.
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
{{--
    Was a hardcoded `bg-[#0B132B]` navy — the last always-dark surface left in
    the product, and an off-palette literal rather than a token. It now paints
    the same `mist-50` canvas as every other page, so the setup wizard no
    longer drops the visitor onto a dark screen mid-onboarding.
--}}
<body class="flex min-h-dvh w-full items-center justify-center bg-mist-50 px-4 py-12 font-sans text-ink-900 antialiased">
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 start-1/4 h-96 w-96 rounded-full bg-brand-500/8 blur-3xl"></div>
        <div class="absolute -bottom-24 end-0 h-80 w-80 rounded-full bg-marker-500/8 blur-3xl"></div>
    </div>

    <div class="relative z-10 w-full {{ $maxWidth }}">
        {{ $slot }}
    </div>

    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    @if (session('flasher'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                Swal.fire({
                    icon: @js(session('flasher.type', 'success')),
                    title: @js(session('flasher.message')),
                    confirmButtonColor: '#714b67',
                    confirmButtonText: 'حسنًا',
                    timer: 4200,
                    timerProgressBar: true,
                });
            });
        </script>
    @endif
</body>
</html>
