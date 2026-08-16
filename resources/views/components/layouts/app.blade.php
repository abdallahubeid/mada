<!DOCTYPE html>
{{--
    Tenant app shell — mirrors Platform Console chrome (layouts/admin.blade.php):
    Arabic RTL, Mada plum design system, dark/light (ADR-15), logical spacing (ADR-10).
--}}
<html lang="ar" dir="rtl" class="h-full scroll-smooth">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <title>{{ $title ?? 'لوحة التحكم' }} · مدى</title>

    <x-site-favicon />

    <x-theme-script />

    @vite(['resources/css/app.css', 'resources/js/app.js'])
    @livewireStyles
    @stack('styles')
    {{ $head ?? '' }}
</head>
@php
    $authUser = auth()->user();
    $initialUnreadNotifications = $authUser?->unreadNotifications()->count() ?? 0;
@endphp
<body
    class="h-full overflow-hidden bg-neutral-50 font-sans text-ink-600 antialiased dark:bg-ink-950 dark:text-mist-300"
    x-data="madaTenantNotificationsShell(@js([
        'indexUrl' => route('tenant.notifications.index'),
        'readAllUrl' => route('tenant.notifications.read-all'),
        'readUrlTemplate' => route('tenant.notifications.read', ['notification' => '__ID__']),
        'unreadCount' => $initialUnreadNotifications,
        'tenantId' => $authUser?->tenant_id,
        'userId' => $authUser?->id,
        'echoEnabled' => (bool) $authUser?->tenant_id,
    ]))"
    x-init="boot()"
    @keydown.escape.window="closeSidebarDrawer(); profileOpen = false; closeNotificationsDrawer()"
>
    <div class="flex h-full min-h-0 w-full">
        <x-layouts.partials.sidebar />

        <div class="flex min-h-0 w-full min-w-0 flex-1 flex-col">
            <x-layouts.partials.topbar :title="$title ?? null" />

            <main class="min-h-0 w-full min-w-0 flex-1 overflow-x-hidden overflow-y-auto p-3 sm:p-6 lg:p-8">
                @if ($errors->any())
                    <div class="mb-4 rounded-xl border border-danger-solid/40 bg-danger-solid/10 px-4 py-3 text-sm text-danger-solid">
                        <ul class="list-disc ps-5">
                            @foreach ($errors->all() as $error)
                                <li>{{ $error }}</li>
                            @endforeach
                        </ul>
                    </div>
                @endif

                <x-announcements-banner />

                {{ $slot }}
            </main>
        </div>
    </div>

    <x-layouts.partials.notifications-drawer />

    {{ $scripts ?? '' }}
    @stack('scripts')

    @livewireScripts

    <script src="https://cdn.jsdelivr.net/npm/sweetalert2@11"></script>
    <script>
        function madaTenantNotificationsShell(config) {
            return {
                sidebarOpen: false,
                profileOpen: false,
                notificationsOpen: false,
                sidebarCollapsed: localStorage.getItem('mada-tenant-sidebar-collapsed') === 'true',
                unreadCount: Number(config.unreadCount || 0),
                notifications: [],
                notificationsLoading: false,
                indexUrl: config.indexUrl,
                readAllUrl: config.readAllUrl,
                readUrlTemplate: config.readUrlTemplate,
                tenantId: config.tenantId,
                userId: config.userId,
                echoEnabled: Boolean(config.echoEnabled),

                toggleSidebar() {
                    this.sidebarCollapsed = ! this.sidebarCollapsed;
                    localStorage.setItem('mada-tenant-sidebar-collapsed', this.sidebarCollapsed);
                },

                closeSidebarDrawer() {
                    this.sidebarOpen = false;
                },

                /*
                 * Opening the drawer IS the acknowledgement — the manual
                 * mark-all-as-read button is gone.
                 *
                 * Order matters: the list is fetched FIRST and with the badge
                 * sync suppressed, so the rows the user is looking at keep the
                 * unread styling they had the instant the drawer opened. Marking
                 * the server read before the fetch would return an all-read list
                 * and grey out the very items the user opened the drawer to see.
                 */
                async openNotificationsDrawer() {
                    this.notificationsOpen = true;
                    await this.loadNotifications({ syncBadge: false });
                    this.acknowledgeAll();
                },

                /*
                 * Dismissal — via the X, the overlay, a click outside, or Escape
                 * — also acknowledges. Guarded on `notificationsOpen` because
                 * `@click.outside` fires on EVERY click in the app, including
                 * while the drawer is closed; without this, any click anywhere
                 * would silently mark every notification read.
                 */
                closeNotificationsDrawer() {
                    if (! this.notificationsOpen) {
                        return;
                    }

                    this.notificationsOpen = false;
                    this.acknowledgeAll();
                },

                /**
                 * Zero the badge instantly, then persist in the background.
                 *
                 * The badge is the source of truth for "is there anything to
                 * persist": it is only ever set from a server response or a
                 * broadcast payload. When it is already 0 there is nothing
                 * unread server-side either, so the request is skipped rather
                 * than fired on every open and close.
                 */
                acknowledgeAll() {
                    const hadUnread = Number(this.unreadCount || 0) > 0;

                    this.unreadCount = 0;

                    if (! hadUnread) {
                        return;
                    }

                    // Deliberately not awaited: acknowledgement must never make
                    // the drawer feel slow. A failure leaves the rows unread
                    // server-side and the next load restores the true count.
                    fetch(this.readAllUrl, {
                        method: 'POST',
                        headers: {
                            'Accept': 'application/json',
                            'X-Requested-With': 'XMLHttpRequest',
                            'X-CSRF-TOKEN': this.csrfToken(),
                        },
                        keepalive: true,
                    }).catch(() => {});
                },

                csrfToken() {
                    return document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '';
                },

                async loadNotifications({ syncBadge = true } = {}) {
                    this.notificationsLoading = true;

                    try {
                        const response = await fetch(this.indexUrl, {
                            headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                            cache: 'no-store',
                        });

                        if (! response.ok) {
                            return;
                        }

                        const payload = await response.json();
                        this.notifications = Array.isArray(payload.notifications) ? payload.notifications : [];

                        if (syncBadge) {
                            this.unreadCount = Number(payload.unread_count || 0);
                        }
                    } finally {
                        this.notificationsLoading = false;
                    }
                },

                async openNotification(item) {
                    if (! item.read_at) {
                        const url = this.readUrlTemplate.replace('__ID__', item.id);
                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': this.csrfToken(),
                            },
                        });

                        if (response.ok) {
                            const payload = await response.json();
                            this.unreadCount = Number(payload.unread_count || 0);
                            item.read_at = new Date().toISOString();
                        }
                    }

                    if (item.url) {
                        window.location.href = item.url;
                    }
                },

                handleRealtimeNotification(payload) {
                    if (typeof payload.unread_count !== 'undefined') {
                        this.unreadCount = Number(payload.unread_count || 0);
                    } else {
                        this.unreadCount = Number(this.unreadCount || 0) + 1;
                    }

                    const item = {
                        id: payload.id || ('live-' + Date.now()),
                        title: payload.title || 'إشعار جديد',
                        message: payload.message || '',
                        url: payload.url || null,
                        icon: payload.icon || 'bell',
                        severity: payload.severity || 'medium',
                        type: payload.type || '',
                        read_at: null,
                        created_at: new Date().toISOString(),
                    };

                    if (! this.notifications.some((n) => String(n.id) === String(item.id))) {
                        this.notifications = [item, ...this.notifications].slice(0, 30);
                    }

                    if (payload.sound && typeof window.madaPlayNotificationSound === 'function') {
                        window.madaPlayNotificationSound();
                    }

                    if (window.Swal) {
                        Swal.fire({
                            toast: true,
                            position: 'top-start',
                            icon: payload.severity === 'critical' || payload.severity === 'high' ? 'warning' : 'info',
                            title: item.title,
                            text: item.message,
                            showConfirmButton: false,
                            timer: 4500,
                            timerProgressBar: true,
                        });
                    }
                },

                boot() {
                    this._onResize = () => {
                        if (window.matchMedia('(min-width: 1024px)').matches) {
                            this.sidebarOpen = false;
                        }
                    };
                    window.addEventListener('resize', this._onResize);

                    if (this.echoEnabled && typeof window.madaListenTenantNotifications === 'function') {
                        window.madaListenTenantNotifications({
                            tenantId: this.tenantId,
                            userId: this.userId,
                            onNotification: (payload) => this.handleRealtimeNotification(payload),
                        });
                    }
                },
            };
        }

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
             * Confirmation intent, set per form with data-swal-variant.
             *
             * The icon, button colour and default copy used to be hardcoded to
             * deletion, so ANY form using data-swal-confirm — approve, submit,
             * disburse — showed a red "نعم، احذف" button over soft-delete
             * boilerplate. Variant keeps `danger` as the default so existing
             * delete forms are unchanged, while non-destructive actions can
             * declare what they actually do.
             */
            const swalVariants = {
                danger: {
                    icon: 'warning',
                    color: '#b42318',
                    confirm: 'نعم، احذف',
                    text: 'سيتم الحذف الناعم ويمكن الاستعادة من سلة المحذوفات.',
                },
                warning: { icon: 'warning', color: '#b45309', confirm: 'نعم، تابع', text: '' },
                success: { icon: 'question', color: '#0f7b3d', confirm: 'نعم، تابع', text: '' },
                info: { icon: 'question', color: '#0369a1', confirm: 'نعم، تابع', text: '' },
            };

            const variant = swalVariants[form.dataset.swalVariant] || swalVariants.danger;

            Swal.fire({
                title: form.dataset.swalTitle || 'هل أنت تأكد من الحذف؟',
                text: form.dataset.swalText || variant.text,
                icon: form.dataset.swalIcon || variant.icon,
                showCancelButton: true,
                confirmButtonText: form.dataset.swalConfirmButton || variant.confirm,
                cancelButtonText: form.dataset.swalCancelButton || 'إلغاء',
                confirmButtonColor: variant.color,
                cancelButtonColor: '#5a5262',
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
                    confirmButtonColor: '#714b67',
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
