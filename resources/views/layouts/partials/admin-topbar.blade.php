@php
    // Mock operator identity for the frontend preview — real Super Admin auth
    // (mandatory 2FA, ADR-14) is wired in the backend phase.
    $adminName = auth()->user()->name ?? 'مشرف المنصّة';
    $adminEmail = auth()->user()->email ?? 'admin@veyra.test';
@endphp

{{-- Sticky, blurred console topbar (≈ Figma's 12px background-blur). --}}
<header class="sticky top-0 z-30 flex h-16 shrink-0 items-center justify-between gap-3 border-b border-mist-200/70 bg-neutral-50/80 px-4 shadow-sm backdrop-blur-md sm:px-6 dark:border-ink-700/70 dark:bg-ink-900/80">
    <div class="flex min-w-0 items-center gap-3">
        <button
            type="button"
            @click="sidebarOpen = true"
            class="rounded-lg p-1.5 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 lg:hidden dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="فتح القائمة"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <div class="min-w-0">
            <h1 class="truncate font-display text-lg font-semibold text-ink-900 dark:text-ink-50">@yield('title', 'لوحة تحكم المنصّة')</h1>
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
        {{-- Global search placeholder (non-functional in this frontend slice) --}}
        <div class="relative hidden md:block">
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input
                type="search"
                placeholder="بحث في المنصّة..."
                class="w-56 rounded-lg border border-mist-200 bg-white py-2 ps-9 pe-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50"
            >
        </div>

        {{-- Appearance toggle (persisted, ADR-15) --}}
        <button
            type="button"
            x-data
            @click="
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('veyra-theme', isDark ? 'dark' : 'light');
            "
            class="rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="تبديل الوضع الليلي"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>

        {{-- Notifications bell --}}
        <a
            href="{{ Route::has('admin.notifications') ? route('admin.notifications') : '#' }}"
            class="relative rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="الإشعارات"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <span class="absolute end-1.5 top-1.5 flex h-4 min-w-4 items-center justify-center rounded-full bg-emerald-400 px-1 text-[10px] font-bold text-emerald-900">3</span>
        </a>

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
                <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-400/15 font-display text-sm font-bold text-emerald-600 dark:text-emerald-400">
                    {{ mb_substr($adminName, 0, 1) }}
                </span>
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
                    <p class="truncate text-sm font-medium text-ink-900 dark:text-ink-50">{{ $adminName }}</p>
                    <p class="truncate text-xs text-mist-500 dark:text-mist-400">{{ $adminEmail }}</p>
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
