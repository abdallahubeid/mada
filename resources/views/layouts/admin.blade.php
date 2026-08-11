<!DOCTYPE html>
{{--
    Super Admin / Platform Console shell (docs/MODULES.md §6). Hardcoded
    `ar`/`rtl` like the marketing/auth layouts — ADR-10 logical properties
    (`ps-*`/`pe-*`/`start`/`end`) still govern, so an LTR locale switcher drops
    in cleanly later. Defaults to the dark elevated theme (the console's
    primary look) while still honouring the persisted user toggle (ADR-15).
--}}
<html lang="ar" dir="rtl" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>@yield('title', 'لوحة تحكم المنصّة') · Veyra</title>

    <x-site-favicon />

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @stack('styles')
    @livewireStyles
</head>
<body
    class="h-full overflow-hidden bg-neutral-50 font-sans text-ink-600 antialiased dark:bg-ink-950 dark:text-mist-300"
    x-data="{
        sidebarOpen: false,
        profileOpen: false,
        impersonating: false,
        sidebarCollapsed: localStorage.getItem('veyra-admin-sidebar-collapsed') === 'true',
        toggleSidebar() {
            this.sidebarCollapsed = ! this.sidebarCollapsed;
            localStorage.setItem('veyra-admin-sidebar-collapsed', this.sidebarCollapsed);
        },
        closeSidebarDrawer() {
            this.sidebarOpen = false;
        },
        init() {
            this._onResize = () => {
                if (window.matchMedia('(min-width: 1024px)').matches) {
                    this.sidebarOpen = false;
                }
            };
            window.addEventListener('resize', this._onResize);
            this.$cleanup?.(() => window.removeEventListener('resize', this._onResize));
        },
    }"
    @keydown.escape.window="closeSidebarDrawer()"
>
    <div class="flex h-full min-h-0 w-full">
        @include('layouts.partials.admin-sidebar')

        <div class="flex min-h-0 w-full min-w-0 flex-1 flex-col">
            @include('layouts.partials.admin-topbar')

            {{--
                Impersonation banner slot (Phase 4, docs/DEVELOPMENT_ROADMAP.md).
                Chrome-only for now: `impersonating` stays false until the real
                impersonation session logic ships.
            --}}
            <div
                x-show="impersonating"
                x-cloak
                class="flex items-center justify-between gap-3 border-b border-amber-500/30 bg-amber-500/10 px-4 py-2 text-sm text-amber-600 sm:px-6 dark:text-amber-400"
            >
                <span class="flex items-center gap-2 font-medium">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" />
                        <path stroke-linecap="round" stroke-linejoin="round" d="M2.036 12.322a1.012 1.012 0 0 1 0-.639C3.423 7.51 7.36 4.5 12 4.5c4.638 0 8.573 3.007 9.963 7.178.07.207.07.431 0 .639C20.577 16.49 16.64 19.5 12 19.5c-4.638 0-8.573-3.007-9.963-7.178Z" />
                    </svg>
                    أنت الآن تتصفّح بصلاحيات مستأجر — الوضع للقراءة فقط.
                </span>
                <button type="button" @click="impersonating = false" class="rounded-lg px-2 py-1 font-semibold underline-offset-2 hover:underline">
                    إنهاء الجلسة
                </button>
            </div>

            <main class="min-h-0 w-full min-w-0 flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-6 lg:p-8">
                @if (session('status'))
                    <div class="mb-4 rounded-xl border border-emerald-400/40 bg-emerald-400/10 px-4 py-3 text-sm font-medium text-emerald-700 dark:text-emerald-300">
                        {{ session('status') }}
                    </div>
                @endif

                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-danger-solid/40 bg-danger-solid/10 px-4 py-3 text-sm text-danger-solid">
                        <ul class="list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                @yield('content')
            </main>
        </div>
    </div>

    @stack('scripts')
    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        document.addEventListener('submit', function (event) {
            const form = event.target;

            if (! (form instanceof HTMLFormElement) || ! form.hasAttribute('data-swal-confirm')) {
                return;
            }

            if (form.dataset.swalConfirmed === '1') {
                return;
            }

            event.preventDefault();
            event.stopPropagation();

            if (typeof Swal === 'undefined') {
                form.dataset.swalConfirmed = '1';
                form.submit();

                return;
            }

            /*
             * Mirrors the tenant shell's variant map (see
             * components/layouts/app.blade.php). `danger` stays the default so
             * every existing delete form behaves exactly as before.
             */
            const swalVariants = {
                danger: {
                    icon: 'warning',
                    color: '#ef4444',
                    confirm: 'نعم، احذف',
                    text: 'سيتم الحذف الناعم ويمكن الاستعادة من سلة المحذوفات.',
                },
                warning: { icon: 'warning', color: '#f59e0b', confirm: 'نعم، تابع', text: '' },
                success: { icon: 'question', color: '#10b981', confirm: 'نعم، تابع', text: '' },
                info: { icon: 'question', color: '#0ea5e9', confirm: 'نعم، تابع', text: '' },
            };

            const variant = swalVariants[form.dataset.swalVariant] || swalVariants.danger;

            Swal.fire({
                title: form.dataset.swalTitle || 'هل أنت متأكد من الحذف؟',
                text: form.dataset.swalText || variant.text,
                icon: form.dataset.swalIcon || variant.icon,
                showCancelButton: true,
                confirmButtonText: form.dataset.swalConfirmButton || variant.confirm,
                cancelButtonText: form.dataset.swalCancelButton || 'إلغاء',
                confirmButtonColor: variant.color,
                cancelButtonColor: '#64748b',
                reverseButtons: true,
            }).then((result) => {
                if (! result.isConfirmed) {
                    return;
                }

                form.dataset.swalConfirmed = '1';
                form.submit();
            });
        }, true);
    </script>
    @if (session('flasher'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                const flasher = @js(session('flasher'));
                const hasUndo = Boolean(flasher.undo_url);

                Swal.fire({
                    toast: hasUndo,
                    position: hasUndo ? 'top-end' : 'center',
                    icon: flasher.type || 'success',
                    title: flasher.message,
                    showConfirmButton: hasUndo,
                    confirmButtonText: flasher.undo_label || 'تراجع',
                    confirmButtonColor: '#4edea3',
                    showCancelButton: false,
                    timer: hasUndo ? 8000 : 3200,
                    timerProgressBar: true,
                }).then((result) => {
                    if (! result.isConfirmed || ! flasher.undo_url) {
                        return;
                    }

                    const form = document.createElement('form');
                    form.method = 'POST';
                    form.action = flasher.undo_url;
                    form.style.display = 'none';

                    const csrf = document.createElement('input');
                    csrf.type = 'hidden';
                    csrf.name = '_token';
                    csrf.value = @js(csrf_token());
                    form.appendChild(csrf);

                    const method = (flasher.undo_method || 'POST').toUpperCase();
                    if (method !== 'POST') {
                        const methodInput = document.createElement('input');
                        methodInput.type = 'hidden';
                        methodInput.name = '_method';
                        methodInput.value = method;
                        form.appendChild(methodInput);
                    }

                    document.body.appendChild(form);
                    form.submit();
                });
            });
        </script>
    @endif
</body>
</html>
