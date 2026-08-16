@php
    /*
     * Platform Console navigation — Super Admin pages (docs/MODULES.md §6).
     * Items with `route => null` (or an unregistered route) render as
     * disabled "قريباً" entries. Items with `children` render as Alpine dropdowns.
     *
     * ─────────────────────────────────────────────────────────────────────
     * GROUP ORDER IS A UX CONTRACT — same four tiers as the tenant shell
     * (components/layouts/partials/sidebar.blade.php):
     *
     *   L1  نظرة عامة              where the shift starts
     *   L2  المستأجرون، التواصل     the operator's actual daily queue
     *   L2  المحتوى                marketing surfaces, edited in bursts
     *   L3  المنصّة                audit history and recovery
     *   L4  الحساب والوصول          security and operator administration
     *
     * Two changes: التواصل now sits above المحتوى, because approving tenants
     * and answering support is what a Super Admin does every day while the
     * landing-page CMS is touched in occasional bursts; and الأسئلة الشائعة
     * moved out of المستأجرون into المحتوى, where it belongs — `faqs` rows are
     * platform-global marketing content (DATABASE_ROADMAP.md §2.1), not
     * tenant data. It stays a top-level item rather than joining the CMS
     * dropdown so its own `faqs.view_any` permission keeps gating it alone.
     * ─────────────────────────────────────────────────────────────────────
     */
    $navGroups = [
        [
            'label' => 'نظرة عامة',
            'items' => [
                [
                    'label' => 'لوحة التحكم',
                    'route' => 'admin.dashboard',
                    'permission' => 'dashboard.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M2.25 12l8.955-8.955a1.125 1.125 0 011.59 0L21.75 12M4.5 9.75v10.125c0 .621.504 1.125 1.125 1.125H9.75v-4.875c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125V21h4.125c.621 0 1.125-.504 1.125-1.125V9.75" />',
                ],
            ],
        ],
        [
            'label' => 'المستأجرون',
            'items' => [
                [
                    'label' => 'إدارة المستأجرين',
                    'route' => 'admin.tenants',
                    'pattern' => 'admin.tenants*',
                    'permission' => 'tenants.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h1.5m-1.5 3h1.5m-1.5 3h1.5m3-6H15m-1.5 3H15m-1.5 3H15M9 21v-3.375c0-.621.504-1.125 1.125-1.125h3.75c.621 0 1.125.504 1.125 1.125V21" />',
                ],
                [
                    'label' => 'الخطط والحدود',
                    'route' => 'admin.plans',
                    'permission' => 'plans.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 12h16.5m-16.5 3.75h16.5M3.75 19.5h16.5M5.625 4.5h12.75a1.875 1.875 0 010 3.75H5.625a1.875 1.875 0 010-3.75z" />',
                ],
            ],
        ],
        [
            'label' => 'التواصل',
            'items' => [
                [
                    'label' => 'الإشعارات',
                    'route' => 'admin.notifications',
                    'permission' => 'notifications.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 005.454-1.31A8.967 8.967 0 0118 9.75V9A6 6 0 006 9v.75a8.967 8.967 0 01-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 01-5.714 0m5.714 0a3 3 0 11-5.714 0" />',
                ],
                [
                    'label' => 'الرسائل والدعم',
                    'route' => 'admin.messages',
                    'permission' => 'support.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 01.865-.501 48.172 48.172 0 003.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0012 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />',
                ],
                [
                    'label' => 'النشرة البريدية',
                    'permission' => 'newsletters.view_any',
                    'pattern' => 'admin.newsletter*',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M21.75 6.75v10.5a2.25 2.25 0 01-2.25 2.25h-15a2.25 2.25 0 01-2.25-2.25V6.75m19.5 0A2.25 2.25 0 0019.5 4.5h-15a2.25 2.25 0 00-2.25 2.25m19.5 0v.243a2.25 2.25 0 01-1.07 1.916l-7.5 4.615a2.25 2.25 0 01-2.36 0L3.32 8.91a2.25 2.25 0 01-1.07-1.916V6.75" />',
                    'children' => [
                        ['label' => 'المشتركين', 'route' => 'admin.newsletter.index', 'pattern' => 'admin.newsletter.index', 'permission' => 'newsletters.view_any'],
                        ['label' => 'الحملات البريدية', 'route' => 'admin.newsletter.campaigns.index', 'pattern' => 'admin.newsletter.campaigns.*', 'permission' => 'newsletters.view_any'],
                    ],
                ],
            ],
        ],
        [
            'label' => 'المحتوى',
            'items' => [
                [
                    'label' => 'محتوى الصفحة الرئيسية',
                    'permission_any' => ['cms.view_any', 'settings.view'],
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />',
                    'pattern' => 'admin.problems*|admin.solutions*|admin.offerings*|admin.modules*|admin.ai-features*|admin.features*|admin.testimonials*|admin.landing.settings*',
                    /*
                     * Children stay in landing-page section order, not
                     * frequency order — this dropdown mirrors the page it
                     * edits, top to bottom, and reshuffling it would make the
                     * CMS harder to reason about, not easier.
                     */
                    'children' => [
                        ['label' => 'المشاكل', 'route' => 'admin.problems.index', 'pattern' => 'admin.problems*', 'permission' => 'cms.view_any'],
                        ['label' => 'الحلول', 'route' => 'admin.solutions.index', 'pattern' => 'admin.solutions*', 'permission' => 'cms.view_any'],
                        ['label' => 'ما نقدمه', 'route' => 'admin.offerings.index', 'pattern' => 'admin.offerings*', 'permission' => 'cms.view_any'],
                        ['label' => 'الموديولات', 'route' => 'admin.modules.index', 'pattern' => 'admin.modules*', 'permission' => 'cms.view_any'],
                        ['label' => 'ميزات الذكاء الاصطناعي', 'route' => 'admin.ai-features.index', 'pattern' => 'admin.ai-features*', 'permission' => 'cms.view_any'],
                        ['label' => 'الميزات العامة', 'route' => 'admin.features.index', 'pattern' => 'admin.features*', 'permission' => 'cms.view_any'],
                        ['label' => 'آراء العملاء', 'route' => 'admin.testimonials.index', 'pattern' => 'admin.testimonials*', 'permission' => 'cms.view_any'],
                        ['label' => 'إعدادات الصفحة الرئيسية', 'route' => 'admin.landing.settings.edit', 'pattern' => 'admin.landing.settings*', 'permission' => 'settings.view'],
                    ],
                ],
                [
                    'label' => 'الأسئلة الشائعة',
                    'route' => 'admin.faqs.index',
                    'pattern' => 'admin.faqs*',
                    'permission' => 'faqs.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9.879 7.519c1.171-1.025 3.071-1.025 4.242 0 1.172 1.025 1.172 2.687 0 3.712-.203.179-.43.326-.67.442-.745.361-1.45.999-1.45 1.827v.75M21 12a9 9 0 11-18 0 9 9 0 0118 0zm-9 5.25h.008v.008H12v-.008z" />',
                ],
            ],
        ],
        [
            'label' => 'المنصّة',
            'items' => [
                [
                    'label' => 'سجل النشاط',
                    'route' => 'admin.audit-log',
                    'permission' => 'audit_logs.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12h3.75M9 15h3.75M9 18h3.75m3 .75H18a2.25 2.25 0 002.25-2.25V6.108c0-1.135-.845-2.098-1.976-2.192a48.424 48.424 0 00-1.123-.08m-5.801 0c-.065.21-.1.433-.1.664 0 .414.336.75.75.75h4.5a.75.75 0 00.75-.75 2.25 2.25 0 00-.1-.664m-5.8 0A2.251 2.251 0 0113.5 2.25H15c1.012 0 1.867.668 2.15 1.586m-5.8 0c-.376.023-.75.05-1.124.08C9.095 4.01 8.25 4.973 8.25 6.108V8.25m0 0H4.875c-.621 0-1.125.504-1.125 1.125v11.25c0 .621.504 1.125 1.125 1.125h9.75c.621 0 1.125-.504 1.125-1.125V9.375c0-.621-.504-1.125-1.125-1.125H8.25z" />',
                ],
                [
                    'label' => 'سلة المحذوفات',
                    'route' => 'admin.trash.index',
                    'pattern' => 'admin.trash*',
                    'permission' => 'trash.view_any',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 01-2.244 2.077H8.084a2.25 2.25 0 01-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 00-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 013.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 00-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 00-7.5 0" />',
                ],
            ],
        ],
        [
            'label' => 'الحساب والوصول',
            'items' => [
                [
                    'label' => 'أمان الحساب',
                    'route' => 'admin.account.security',
                    'permission' => 'account.security.view',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75L11.25 15 15 9.75M21 12c0 1.268-.63 2.39-1.593 3.068a3.745 3.745 0 01-1.043 3.296 3.745 3.745 0 01-3.296 1.043A3.745 3.745 0 0112 21c-1.268 0-2.39-.63-3.068-1.593a3.746 3.746 0 01-3.296-1.043 3.745 3.745 0 01-1.043-3.296A3.745 3.745 0 013 12c0-1.268.63-2.39 1.593-3.068a3.745 3.745 0 011.043-3.296 3.746 3.746 0 013.296-1.043A3.746 3.746 0 0112 3c1.268 0 2.39.63 3.068 1.593a3.746 3.746 0 013.296 1.043 3.746 3.746 0 011.043 3.296A3.745 3.745 0 0121 12z" />',
                ],
                [
                    'label' => 'مديرو المنصّة',
                    'permission_any' => ['admins.view_any', 'roles.view_any'],
                    'pattern' => 'admin.admins*|admin.roles*',
                    'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 002.625.372 9.337 9.337 0 004.121-.952 4.125 4.125 0 00-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 018.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0111.964-3.07M12 6.375a3.375 3.375 0 11-6.75 0 3.375 3.375 0 016.75 0zm8.25 2.25a2.625 2.625 0 11-5.25 0 2.625 2.625 0 015.25 0z" />',
                    'children' => [
                        [
                            'label' => 'إدارة المستخدمين / المشرفين',
                            'route' => 'admin.admins',
                            'pattern' => 'admin.admins*',
                            'permission' => 'admins.view_any',
                        ],
                        [
                            'label' => 'الأدوار والصلاحيات',
                            'route' => 'admin.roles.index',
                            'pattern' => 'admin.roles*',
                            'permission' => 'roles.view_any',
                        ],
                    ],
                ],
            ],
        ],
    ];
@endphp

{{--
    Off-canvas drawer below lg. Root cause of prior overlay: `end-0` + `translate-x-full`
    in RTL moved the closed sidebar ONTO the canvas instead of off-screen.
    Sidebar docks at inline-start (right in RTL). Closed = -translate-x-full / rtl:translate-x-full.
--}}
<div
    x-show="sidebarOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="closeSidebarDrawer()"
    class="fixed inset-0 z-40 bg-ink-950/60 lg:hidden"
    aria-hidden="true"
></div>

<aside
    id="admin-sidebar"
    :class="{
        'translate-x-0 shadow-2xl pointer-events-auto': sidebarOpen,
        '-translate-x-full rtl:translate-x-full max-lg:pointer-events-none': ! sidebarOpen,
        'lg:w-20': sidebarCollapsed,
        'lg:w-64': ! sidebarCollapsed,
    }"
    :aria-hidden="(!sidebarOpen).toString()"
    class="fixed inset-y-0 start-0 z-50 flex w-64 max-w-[min(16rem,85vw)] shrink-0 -translate-x-full flex-col border-e border-mist-200 bg-white transition-transform duration-300 ease-out rtl:translate-x-full lg:static lg:z-auto lg:max-w-none lg:translate-x-0 lg:pointer-events-auto lg:shadow-none lg:rtl:translate-x-0 dark:border-ink-700 dark:bg-ink-900"
    role="navigation"
    aria-label="قائمة لوحة التحكم"
>
    @php
        $adminLogoUrl = \App\Models\Setting::assetUrl($settings['site_logo'] ?? null);
    @endphp
    <div class="flex h-16 shrink-0 items-center gap-2 border-b border-mist-200 px-6 dark:border-ink-700/70" :class="sidebarCollapsed && 'lg:justify-center lg:px-0'">
        <a
            href="{{ route('landing') }}"
            class="flex min-w-0 items-center gap-2"
            :class="sidebarCollapsed && 'lg:justify-center'"
            title="الصفحة الرئيسية"
            aria-label="الصفحة الرئيسية — مدى"
            @click="closeSidebarDrawer()"
        >
            @if ($adminLogoUrl)
                <img
                    src="{{ $adminLogoUrl }}"
                    alt="مدى"
                    class="h-8 max-h-8 w-auto max-w-[140px] shrink-0 object-contain object-start"
                    :class="sidebarCollapsed && 'lg:max-w-8'"
                >
                <div class="leading-tight" x-show="! sidebarCollapsed">
                    <span class="block text-xs font-medium uppercase tracking-wide text-mist-500">Platform Console</span>
                </div>
            @else
                <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-lg bg-brand-500 font-display text-sm font-medium text-white shadow-glow">م</span>
                <div class="leading-tight" x-show="! sidebarCollapsed">
                    <span class="block font-display text-sm font-medium text-ink-900 dark:text-ink-50">مدى</span>
                    <span class="block text-xs font-medium uppercase tracking-wide text-mist-500">Platform Console</span>
                </div>
            @endif
        </a>
        <button
            type="button"
            @click="closeSidebarDrawer()"
            class="ms-auto rounded-lg p-1.5 text-mist-500 transition hover:bg-mist-100 lg:hidden dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="إغلاق القائمة"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <nav class="flex-1 space-y-6 overflow-y-auto px-3 py-6">
        @foreach ($navGroups as $group)
            @php
                $visibleItems = collect($group['items'])->filter(function (array $item): bool {
                    if (! empty($item['permission'])) {
                        return auth()->user()?->can($item['permission']) ?? false;
                    }

                    if (! empty($item['permission_any'])) {
                        return collect($item['permission_any'])->contains(
                            fn (string $permission): bool => auth()->user()?->can($permission) ?? false
                        );
                    }

                    return true;
                })->map(function (array $item): array {
                    if (! empty($item['children'])) {
                        $item['children'] = collect($item['children'])->filter(function (array $child): bool {
                            if (empty($child['permission'])) {
                                return true;
                            }

                            return auth()->user()?->can($child['permission']) ?? false;
                        })->values()->all();

                        if ($item['children'] === [] && empty($item['route'])) {
                            return null;
                        }
                    }

                    return $item;
                })->filter()->values()->all();
            @endphp
            @continue($visibleItems === [])
            <div>
                <p class="px-3 text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-mist-400" x-show="! sidebarCollapsed">
                    {{ $group['label'] }}
                </p>
                <div class="mx-auto my-2 h-px w-6 bg-mist-200 dark:bg-ink-700" x-show="sidebarCollapsed" x-cloak></div>
                <ul class="mt-2 space-y-1">
                    @foreach ($visibleItems as $item)
                        @php
                            $hasChildren = ! empty($item['children']);
                            $visibleChildren = $hasChildren ? $item['children'] : [];
                            $childActive = $hasChildren && collect($visibleChildren)->contains(
                                fn (array $child): bool => ($child['route'] ?? null)
                                    && Route::has($child['route'])
                                    && request()->routeIs($child['pattern'] ?? $child['route'])
                            );
                            $hasRoute = ! $hasChildren && ($item['route'] ?? null) && Route::has($item['route']);
                            $isActive = $hasChildren
                                ? $childActive
                                : ($hasRoute && request()->routeIs($item['pattern'] ?? $item['route']));
                        @endphp
                        <li>
                            @if ($hasChildren)
                                <div
                                    x-data="{ open: {{ $childActive ? 'true' : 'false' }} }"
                                    class="space-y-1"
                                >
                                    <button
                                        type="button"
                                        @click="open = ! open"
                                        x-bind:title="sidebarCollapsed ? @js($item['label']) : null"
                                        :class="sidebarCollapsed && 'lg:justify-center'"
                                        @class([
                                            'group flex w-full items-center gap-3 rounded-lg border-s-2 px-3 py-2 text-sm font-medium transition-all duration-300 ease-out active:scale-[0.98]',
                                            'border-brand-500 bg-brand-500/10 text-brand-600 dark:text-brand-300' => $isActive,
                                            'border-transparent text-ink-600 hover:border-mist-300 hover:bg-mist-100 dark:text-mist-300 dark:hover:border-ink-600 dark:hover:bg-ink-800' => ! $isActive,
                                        ])
                                    >
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 {{ $isActive ? 'text-brand-500 dark:text-brand-300' : 'text-mist-400 group-hover:text-mist-500 dark:text-mist-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            {!! $item['icon'] !!}
                                        </svg>
                                        <span x-show="! sidebarCollapsed" class="flex-1 text-start">{{ $item['label'] }}</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 transition-transform duration-200" :class="open && 'rotate-180'" x-show="! sidebarCollapsed" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                            <path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" />
                                        </svg>
                                    </button>
                                    <ul
                                        x-show="open && ! sidebarCollapsed"
                                        x-cloak
                                        x-transition
                                        class="ms-4 space-y-1 border-s border-mist-200 ps-3 dark:border-ink-700"
                                    >
                                        @foreach ($visibleChildren as $child)
                                            @php
                                                $childHasRoute = ($child['route'] ?? null) && Route::has($child['route']);
                                                $childIsActive = $childHasRoute && request()->routeIs($child['pattern'] ?? $child['route']);
                                            @endphp
                                            @if ($childHasRoute)
                                                <li>
                                                    <a
                                                        href="{{ route($child['route']) }}"
                                                        @click="closeSidebarDrawer()"
                                                        @class([
                                                            'block rounded-lg px-3 py-1.5 text-sm transition duration-200',
                                                            'bg-brand-500/10 font-semibold text-brand-600 dark:text-brand-300' => $childIsActive,
                                                            'text-mist-500 hover:bg-mist-100 hover:text-ink-700 dark:text-mist-400 dark:hover:bg-ink-800 dark:hover:text-mist-100' => ! $childIsActive,
                                                        ])
                                                    >{{ $child['label'] }}</a>
                                                </li>
                                            @endif
                                        @endforeach
                                    </ul>
                                </div>
                            @elseif ($hasRoute)
                                <a
                                    href="{{ route($item['route']) }}"
                                    @click="closeSidebarDrawer()"
                                    x-bind:title="sidebarCollapsed ? @js($item['label']) : null"
                                    :class="sidebarCollapsed && 'lg:justify-center'"
                                    @class([
                                        'group flex items-center gap-3 rounded-lg border-s-2 px-3 py-2 text-sm font-medium transition-all duration-300 ease-out active:scale-[0.98]',
                                        'border-brand-500 bg-brand-500/10 text-brand-600 dark:text-brand-300' => $isActive,
                                        'border-transparent text-ink-600 hover:border-mist-300 hover:bg-mist-100 dark:text-mist-300 dark:hover:border-ink-600 dark:hover:bg-ink-800' => ! $isActive,
                                    ])
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 {{ $isActive ? 'text-brand-500 dark:text-brand-300' : 'text-mist-400 group-hover:text-mist-500 dark:text-mist-500' }}" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        {!! $item['icon'] !!}
                                    </svg>
                                    <span x-show="! sidebarCollapsed" class="{{ $isActive ? 'underline decoration-brand-500 decoration-2 underline-offset-4' : 'underline-offset-2' }}">{{ $item['label'] }}</span>
                                </a>
                            @else
                                <span
                                    x-bind:title="sidebarCollapsed ? @js($item['label']) : null"
                                    :class="sidebarCollapsed && 'lg:justify-center'"
                                    class="flex items-center gap-3 rounded-lg border-s-2 border-transparent px-3 py-2 text-sm text-mist-400 dark:text-mist-600"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                                        {!! $item['icon'] !!}
                                    </svg>
                                    <span class="flex-1" x-show="! sidebarCollapsed">{{ $item['label'] }}</span>
                                    <span class="rounded-md bg-mist-100 px-2 py-0.5 text-xs font-medium uppercase tracking-wide text-mist-500 dark:bg-ink-800 dark:text-mist-400" x-show="! sidebarCollapsed">
                                        قريباً
                                    </span>
                                </span>
                            @endif
                        </li>
                    @endforeach
                </ul>
            </div>
        @endforeach
    </nav>

    <div class="hidden shrink-0 border-t border-mist-200 p-3 lg:block dark:border-ink-700/70">
        <button
            type="button"
            @click="toggleSidebar()"
            x-bind:title="sidebarCollapsed ? 'توسيع القائمة' : 'طيّ القائمة'"
            :class="sidebarCollapsed && 'lg:justify-center'"
            class="flex w-full items-center gap-3 rounded-lg px-3 py-2 text-sm font-medium text-mist-500 transition duration-200 hover:bg-mist-100 hover:text-ink-700 active:scale-[0.98] dark:text-mist-400 dark:hover:bg-ink-800 dark:hover:text-mist-100"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 shrink-0 transition-transform duration-300 rtl:-scale-x-100" :class="sidebarCollapsed && 'rotate-180'" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 19.5 8.25 12l7.5-7.5" />
            </svg>
            <span x-show="! sidebarCollapsed">طيّ القائمة</span>
        </button>
    </div>
</aside>
