@props([
    'title' => null,
    'description' => 'منصة Veyra ERP الشاملة لإدارة الموارد البشرية والمشاريع والرواتب — أتمتة كاملة لعمليات مؤسستك في نظام واحد آمن ومعزول لكل عميل.',
])
<!DOCTYPE html>
{{--
    The public marketing site is Arabic-first per the Figma reference and is
    hardcoded to `ar`/`rtl` here rather than following `app()->getLocale()`
    (currently `en`, used by the authenticated app shell). A locale switcher
    for the marketing site is a separate future concern — docs/ARCHITECTURE.md
    ADR-10 still governs: everything below uses logical `ps-*`/`pe-*`/`start`/`end`
    properties so this keeps working the moment that switcher exists.
--}}
<html lang="ar" dir="rtl" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>{{ $title ?? 'Veyra ERP' }}</title>

    <x-site-favicon />

    {{-- SEO & Open Graph (docs/MARKETING.md §5.4) --}}
    <meta name="description" content="{{ $description }}">
    <meta property="og:type" content="website">
    <meta property="og:site_name" content="Veyra ERP">
    <meta property="og:title" content="{{ $title ?? 'Veyra ERP' }}">
    <meta property="og:description" content="{{ $description }}">
    <meta property="og:url" content="{{ url()->current() }}">
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="{{ $title ?? 'Veyra ERP' }}">
    <meta name="twitter:description" content="{{ $description }}">

    {{-- Applied before first paint to avoid a flash of the wrong theme (docs/DESIGN_SYSTEM.md §2, ADR-15). --}}
    <script>
        (function () {
            const stored = localStorage.getItem('veyra-theme');
            const prefersDark = window.matchMedia('(prefers-color-scheme: dark)').matches;
            document.documentElement.classList.toggle('dark', stored ? stored === 'dark' : prefersDark);
        })();
    </script>

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    <script src="https://code.iconify.design/iconify-icon/2.3.0/iconify-icon.min.js"></script>
    @livewireStyles
</head>
<body class="h-full bg-ink-100 font-sans text-ink-600 antialiased dark:bg-ink-950 dark:text-mist-300">
    {{ $slot }}

    {{-- Animated marketing stat counters (x-marketing.stat-counter). Registered
         before Livewire boots Alpine so alpine:init listeners are ready. --}}
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('veyraStatCounter', (config) => ({
                value: Number(config.value) || 0,
                prefix: config.prefix || '',
                suffix: config.suffix || '',
                decimals: Number(config.decimals) || 0,
                duration: Number(config.duration) || 1800,
                separator: config.separator !== false,
                current: 0,
                started: false,
                frame: null,

                get display() {
                    return this.prefix + this.format(this.current) + this.suffix;
                },

                format(n) {
                    const fixed = Number(n).toFixed(this.decimals);
                    if (! this.separator) {
                        return fixed;
                    }

                    const [intPart, decPart] = fixed.split('.');
                    const withCommas = intPart.replace(/\B(?=(\d{3})+(?!\d))/g, ',');

                    return this.decimals > 0 ? `${withCommas}.${decPart}` : withCommas;
                },

                init() {
                    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
                        this.current = this.value;
                        return;
                    }

                    const observer = new IntersectionObserver((entries) => {
                        entries.forEach((entry) => {
                            if (entry.isIntersecting && ! this.started) {
                                this.started = true;
                                this.animate();
                                observer.disconnect();
                            }
                        });
                    }, { threshold: 0.35 });

                    observer.observe(this.$el);
                },

                animate() {
                    const start = performance.now();
                    const from = 0;
                    const to = this.value;
                    const duration = this.duration;

                    const tick = (now) => {
                        const progress = Math.min((now - start) / duration, 1);
                        const eased = 1 - Math.pow(1 - progress, 3);
                        this.current = from + (to - from) * eased;

                        if (progress < 1) {
                            this.frame = requestAnimationFrame(tick);
                        } else {
                            this.current = to;
                        }
                    };

                    this.frame = requestAnimationFrame(tick);
                },

                destroy() {
                    if (this.frame) {
                        cancelAnimationFrame(this.frame);
                    }
                },
            }));
        });
    </script>

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
