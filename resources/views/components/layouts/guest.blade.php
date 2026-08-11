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
    <title>{{ $title ?? 'Veyra ERP' }}</title>

    <x-site-favicon />

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
</head>
<body class="flex min-h-dvh w-full items-center justify-center bg-[#0B132B] px-4 py-12 font-sans text-ink-50 antialiased dark:bg-[#0F172A]">
    <div class="pointer-events-none fixed inset-0 overflow-hidden" aria-hidden="true">
        <div class="absolute -top-32 start-1/4 h-96 w-96 rounded-full bg-emerald-400/10 blur-3xl"></div>
        <div class="absolute -bottom-24 end-0 h-80 w-80 rounded-full bg-emerald-500/10 blur-3xl"></div>
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
                    confirmButtonColor: '#4edea3',
                    confirmButtonText: 'حسنًا',
                    timer: 4200,
                    timerProgressBar: true,
                });
            });
        </script>
    @endif
</body>
</html>
