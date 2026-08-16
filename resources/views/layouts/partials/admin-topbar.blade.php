@php
    // Mock operator identity for the frontend preview — real Super Admin auth
    // (mandatory 2FA, ADR-14) is wired in the backend phase.
    $adminUser = auth()->user();
    $adminUser?->loadMissing('avatar');
    $adminName = $adminUser->name ?? 'مشرف المنصّة';
    $adminEmail = $adminUser->email ?? 'admin@mada.test';
    $adminAvatarUrl = $adminUser?->avatar_url;

    $chromeBadges = app(\App\Services\Admin\AdminChromeBadges::class)->snapshot();

    $searchContext = match (true) {
        request()->routeIs('admin.newsletter.campaigns*') => 'newsletter_campaigns',
        request()->routeIs('admin.newsletter*') => 'newsletter',
        request()->routeIs('admin.messages*') => 'messages',
        request()->routeIs('admin.tenants*') => 'tenants',
        request()->routeIs('admin.plans*') => 'plans',
        request()->routeIs('admin.faqs*') => 'faqs',
        request()->routeIs('admin.notifications') => 'notifications',
        request()->routeIs('admin.problems*', 'admin.solutions*', 'admin.offerings*', 'admin.modules*', 'admin.ai-features*', 'admin.features*', 'admin.testimonials*', 'admin.landing.settings*') => 'landing',
        request()->routeIs('admin.dashboard') => 'dashboard',
        default => null,
    };
@endphp

{{-- Sticky, blurred console topbar (≈ Figma's 12px background-blur effect). --}}
<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-mist-200/70 bg-neutral-50/80 px-4 shadow-sm backdrop-blur-md sm:px-6 dark:border-ink-700/70 dark:bg-ink-900/80">
    <div class="flex min-w-0 items-center gap-3">
        <button
            type="button"
            @click="sidebarOpen = true"
            class="rounded-lg p-1.5 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 lg:hidden dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="فتح القائمة"
            :aria-expanded="sidebarOpen.toString()"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <div class="min-w-0">
            <h1 class="truncate font-display text-lg font-medium text-ink-900 dark:text-ink-50">@yield('title', 'لوحة تحكم المنصّة')</h1>
            <nav aria-label="مسار التنقّل" class="hidden text-xs text-mist-500 sm:block dark:text-mist-400">
                @hasSection('breadcrumbs')
                    @yield('breadcrumbs')
                @else
                    <span>لوحة تحكم المنصّة</span>
                @endif
            </nav>
        </div>
    </div>

    <div class="flex items-center gap-2">
        <div
            class="flex items-center gap-2"
            x-data="madaAdminChrome(@js([
                'pollUrl' => route('admin.chrome.poll'),
                'suggestUrl' => route('admin.search.suggest'),
                'searchUrl' => route('admin.search'),
                'messagesUnread' => $chromeBadges['messages_unread'],
                'notificationsUnread' => $chromeBadges['notifications_unread'],
                'signature' => $chromeBadges['signature'],
                'pollIntervalMs' => 7000,
                'minQueryLength' => \App\Services\Admin\GlobalSearch::MIN_QUERY_LENGTH,
                'context' => $searchContext,
                'echoEnabled' => auth()->check(),
            ]))"
            x-init="boot()"
        >
            {{-- Global live search --}}
            <div class="relative hidden md:block" @click.outside="closeSuggestions()">
                <form method="GET" action="{{ route('admin.search') }}" @submit="closeSuggestions()">
                    <input type="hidden" name="context" value="{{ $searchContext }}">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
                    </svg>
                    <input
                        type="search"
                        name="q"
                        x-model="query"
                        @input.debounce.250ms="fetchSuggestions()"
                        @keydown.escape.prevent="closeSuggestions()"
                        @focus="fetchSuggestions()"
                        placeholder="بحث في المنصّة..."
                        autocomplete="off"
                        class="w-56 rounded-lg border border-mist-200 bg-white py-2 ps-9 pe-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 lg:w-72 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50"
                    >
                </form>

                <div
                    x-show="suggestionsOpen"
                    x-cloak
                    x-transition:enter="transition ease-out duration-150"
                    x-transition:enter-start="opacity-0 -translate-y-1"
                    x-transition:enter-end="opacity-100 translate-y-0"
                    x-transition:leave="transition ease-in duration-100"
                    x-transition:leave-start="opacity-100 translate-y-0"
                    x-transition:leave-end="opacity-0 -translate-y-1"
                    class="absolute start-0 end-0 top-full z-50 mt-2 max-h-96 w-[22rem] max-w-[min(22rem,calc(100vw-2rem))] overflow-y-auto rounded-xl border border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800"
                >
                    <template x-if="loadingSuggestions">
                        <p class="px-4 py-3 text-sm text-mist-500 dark:text-mist-400">جاري البحث…</p>
                    </template>

                    <template x-if="! loadingSuggestions && suggestionGroups.length === 0 && query.trim().length >= minQueryLength">
                        <p class="px-4 py-3 text-sm text-mist-500 dark:text-mist-400">لا توجد نتائج مطابقة.</p>
                    </template>

                    <template x-for="group in suggestionGroups" :key="group.key">
                        <div class="border-b border-mist-100 last:border-b-0 dark:border-ink-700">
                            <p class="px-4 pt-3 pb-1 text-xs font-semibold tracking-wide text-mist-400 uppercase dark:text-mist-500" x-text="group.label"></p>
                            <template x-for="item in group.items" :key="item.url + (item.anchor || '')">
                                <a
                                    :href="item.url"
                                    class="block px-3 py-2 transition hover:bg-mist-50 dark:hover:bg-ink-700"
                                    @click="openSuggestion(item, $event)"
                                >
                                    <p class="truncate text-sm font-medium text-ink-900 dark:text-ink-50" x-text="item.title"></p>
                                    <p class="mt-0.5 truncate text-xs text-mist-500 dark:text-mist-400" x-text="item.subtitle"></p>
                                </a>
                            </template>
                        </div>
                    </template>

                    <template x-if="! loadingSuggestions && query.trim().length >= minQueryLength">
                        <a
                            :href="searchUrl + '?q=' + encodeURIComponent(query.trim())"
                            class="block border-t border-mist-100 px-3 py-2 text-center text-sm font-semibold text-brand-600 transition hover:bg-brand-500/5 dark:border-ink-700 dark:text-brand-300"
                            @click="closeSuggestions()"
                        >
                            عرض كل النتائج
                        </a>
                    </template>
                </div>
            </div>

            {{-- Messages --}}
            <a
                href="{{ route('admin.messages') }}"
                class="relative rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-800"
                aria-label="الرسائل"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M20.25 8.511c.884.284 1.5 1.128 1.5 2.097v4.286c0 1.136-.847 2.1-1.98 2.193-.34.027-.68.052-1.02.072v3.091l-3-3c-1.354 0-2.694-.055-4.02-.163a2.115 2.115 0 0 1-.825-.242m9.345-8.334a2.126 2.126 0 0 0-.476-.095 48.64 48.64 0 0 0-8.048 0c-1.131.094-1.976 1.057-1.976 2.192v4.286c0 .837.46 1.58 1.155 1.951m9.345-8.334V6.637c0-1.621-1.152-3.026-2.76-3.235A48.455 48.455 0 0 0 11.25 3c-2.115 0-4.198.137-6.24.402-1.608.209-2.76 1.614-2.76 3.235v6.226c0 1.621 1.152 3.026 2.76 3.235.577.075 1.157.14 1.74.194V21l4.155-4.155" />
                </svg>
                <span
                    x-show="messagesUnread > 0"
                    x-cloak
                    class="absolute end-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger-solid px-1 text-xs font-bold text-white"
                    x-text="badgeLabel(messagesUnread)"
                ></span>
            </a>

            {{-- Notifications bell --}}
            <a
                href="{{ route('admin.notifications') }}"
                class="relative rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-800"
                aria-label="الإشعارات"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
                </svg>
                <span
                    x-show="notificationsUnread > 0"
                    x-cloak
                    class="absolute end-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger-solid px-1 text-xs font-bold text-white"
                    x-text="badgeLabel(notificationsUnread)"
                ></span>
            </a>
        </div>

        {{-- Profile menu --}}
        <div class="relative" @click.outside="profileOpen = false">
            <button
                type="button"
                @click="profileOpen = !profileOpen"
                class="flex items-center gap-2 rounded-lg border-s border-mist-200 ps-2 transition duration-200 ease-in-out sm:ps-3 dark:border-ink-700"
            >
                <div class="hidden text-end sm:block">
                    <p class="text-sm font-medium text-ink-900 dark:text-ink-50">{{ $adminName }}</p>
                    <p class="text-xs text-mist-500 dark:text-mist-400">مشرف المنصّة</p>
                </div>
                @if ($adminAvatarUrl)
                    <img
                        src="{{ $adminAvatarUrl }}"
                        alt="{{ $adminName }}"
                        class="h-8 w-8 rounded-full border border-slate-700 object-cover"
                    >
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-full border border-slate-700 bg-brand-500/15 font-display text-sm font-medium text-brand-600 dark:text-brand-300">
                        {{ mb_substr($adminName, 0, 1) }}
                    </span>
                @endif
            </button>

            <div
                x-show="profileOpen"
                x-cloak
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 -translate-y-1"
                x-transition:enter-end="opacity-100 translate-y-0"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0"
                x-transition:leave-end="opacity-0 -translate-y-1"
                class="absolute end-0 mt-2 w-56 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-xl dark:border-ink-600 dark:bg-ink-800"
            >
                <div class="border-b border-mist-100 px-4 py-3 dark:border-ink-700">
                    <div class="flex items-center gap-3">
                        @if ($adminAvatarUrl)
                            <img
                                src="{{ $adminAvatarUrl }}"
                                alt="{{ $adminName }}"
                                class="h-8 w-8 shrink-0 rounded-full border border-slate-700 object-cover"
                            >
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-900 dark:text-ink-50">{{ $adminName }}</p>
                            <p class="truncate text-xs text-mist-500 dark:text-mist-400">{{ $adminEmail }}</p>
                        </div>
                    </div>
                </div>

                <a href="{{ Route::has('admin.profile') ? route('admin.profile') : '#' }}" class="flex items-center gap-2 px-4 py-2 text-sm text-ink-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M17.982 18.725A7.488 7.488 0 0 0 12 15.75a7.488 7.488 0 0 0-5.982 2.975m11.963 0a9 9 0 1 0-11.963 0m11.963 0A8.966 8.966 0 0 1 12 21a8.966 8.966 0 0 1-5.982-2.275M15 9.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                    الملف الشخصي
                </a>
                <a href="{{ Route::has('admin.account.security') ? route('admin.account.security') : '#' }}" class="flex items-center gap-2 px-4 py-2 text-sm text-ink-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>
                    أمان الحساب
                </a>
                <a href="{{ Route::has('admin.admins') ? route('admin.admins') : '#' }}" class="flex items-center gap-2 px-4 py-2 text-sm text-ink-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15 19.128a9.38 9.38 0 0 0 2.625.372 9.337 9.337 0 0 0 4.121-.952 4.125 4.125 0 0 0-7.533-2.493M15 19.128v-.003c0-1.113-.285-2.16-.786-3.07M15 19.128v.106A12.318 12.318 0 0 1 8.624 21c-2.331 0-4.512-.645-6.374-1.766l-.001-.109a6.375 6.375 0 0 1 11.964-3.07M12 6.375a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0Z" /></svg>
                    مديرو المنصّة
                </a>

                <form method="POST" action="{{ route('logout') }}" class="border-t border-mist-100 dark:border-ink-700">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-danger-solid transition hover:bg-danger-solid/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" /></svg>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>

@once
    @push('styles')
        <style>
            @keyframes mada-search-flash {
                0%, 100% { background-color: transparent; }
                25%, 55% { background-color: rgb(250 204 21 / 0.4); }
            }
            .mada-search-flash {
                animation: mada-search-flash 1.4s ease-in-out 2;
            }
        </style>
    @endpush
    @push('scripts')
        <script>
            function madaAdminChrome(config) {
                return {
                    pollUrl: config.pollUrl,
                    suggestUrl: config.suggestUrl,
                    searchUrl: config.searchUrl,
                    messagesUnread: config.messagesUnread || 0,
                    notificationsUnread: config.notificationsUnread || 0,
                    signature: config.signature || '',
                    pollIntervalMs: config.pollIntervalMs || 7000,
                    minQueryLength: config.minQueryLength || 2,
                    context: config.context || null,
                    echoEnabled: Boolean(config.echoEnabled),
                    query: '',
                    suggestionGroups: [],
                    suggestionsOpen: false,
                    loadingSuggestions: false,
                    pollTimer: null,
                    suggestAbort: null,

                    boot() {
                        this.pollBadges();
                        this.pollTimer = setInterval(() => this.pollBadges(), this.pollIntervalMs);
                        document.addEventListener('visibilitychange', () => {
                            if (document.visibilityState === 'visible') {
                                this.pollBadges();
                            }
                        });

                        const params = new URLSearchParams(window.location.search);
                        const highlight = params.get('highlight');
                        if (highlight) {
                            setTimeout(() => this.highlightInPage(highlight), 120);
                        }

                        this.listenForRealtimeNotifications();
                    },

                    listenForRealtimeNotifications() {
                        if (! this.echoEnabled || ! window.Echo) {
                            return;
                        }

                        window.Echo.private('admin.notifications')
                            .listen('.PlatformNotificationCreated', (payload) => {
                                if (typeof payload.unread_count !== 'undefined') {
                                    this.notificationsUnread = Number(payload.unread_count || 0);
                                } else {
                                    this.notificationsUnread = Number(this.notificationsUnread || 0) + 1;
                                }

                                if (window.Swal) {
                                    Swal.fire({
                                        toast: true,
                                        position: 'top-start',
                                        icon: 'info',
                                        title: payload.title || 'إشعار جديد',
                                        text: payload.body || '',
                                        showConfirmButton: false,
                                        timer: 4500,
                                        timerProgressBar: true,
                                    });
                                }
                            });
                    },

                    badgeLabel(count) {
                        return count > 99 ? '99+' : String(count);
                    },

                    async pollBadges() {
                        try {
                            const response = await fetch(this.pollUrl, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                cache: 'no-store',
                            });

                            if (! response.ok) {
                                return;
                            }

                            const data = await response.json();

                            if (data.signature && data.signature !== this.signature) {
                                this.signature = data.signature;
                            }

                            this.messagesUnread = Number(data.messages_unread || 0);
                            this.notificationsUnread = Number(data.notifications_unread || 0);
                        } catch (e) {
                            // Keep last known counts on transient network errors.
                        }
                    },

                    closeSuggestions() {
                        this.suggestionsOpen = false;
                    },

                    openSuggestion(item, event) {
                        if (item && item.mode === 'scroll' && item.anchor && item.scope && item.scope === this.context) {
                            const el = document.getElementById(item.anchor)
                                || document.querySelector('[data-mada-search="' + item.anchor.replace(/^mada-search-/, '') + '"]');

                            if (el) {
                                event.preventDefault();
                                this.closeSuggestions();
                                this.highlightInPage(item.anchor);
                                return;
                            }
                        }

                        this.closeSuggestions();
                    },

                    highlightInPage(token) {
                        if (! token) {
                            return false;
                        }

                        const id = String(token).startsWith('mada-search-')
                            ? String(token)
                            : 'mada-search-' + String(token);
                        const short = id.replace(/^mada-search-/, '');
                        const el = document.getElementById(id)
                            || document.querySelector('[data-mada-search="' + short + '"]');

                        if (! el) {
                            return false;
                        }

                        el.scrollIntoView({ behavior: 'smooth', block: 'center' });
                        el.classList.remove('mada-search-flash');
                        void el.offsetWidth;
                        el.classList.add('mada-search-flash');

                        return true;
                    },

                    async fetchSuggestions() {
                        const q = this.query.trim();

                        if (q.length < this.minQueryLength) {
                            this.suggestionGroups = [];
                            this.suggestionsOpen = false;
                            this.loadingSuggestions = false;

                            return;
                        }

                        this.loadingSuggestions = true;
                        this.suggestionsOpen = true;

                        if (this.suggestAbort) {
                            this.suggestAbort.abort();
                        }

                        this.suggestAbort = new AbortController();

                        try {
                            let url = this.suggestUrl + '?q=' + encodeURIComponent(q);
                            if (this.context) {
                                url += '&context=' + encodeURIComponent(this.context);
                            }

                            const response = await fetch(url, {
                                headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' },
                                cache: 'no-store',
                                signal: this.suggestAbort.signal,
                            });

                            if (! response.ok) {
                                return;
                            }

                            const data = await response.json();
                            this.suggestionGroups = data.groups || [];
                            this.suggestionsOpen = true;
                        } catch (e) {
                            if (e.name === 'AbortError') {
                                return;
                            }
                        } finally {
                            this.loadingSuggestions = false;
                        }
                    },
                };
            }
        </script>
    @endpush
@endonce
