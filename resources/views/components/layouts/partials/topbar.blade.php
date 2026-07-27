@props(['title' => null])

{{-- Sticky, blurred navbar (backdrop-blur-md ≈ Figma's 12px background-blur effect on the app topbar). --}}
<header class="sticky top-0 z-40 flex h-16 shrink-0 items-center justify-between border-b border-mist-200/70 bg-white/80 px-4 shadow-sm backdrop-blur-md dark:border-ink-600/70 dark:bg-ink-900/80 sm:px-6">
    <div class="flex items-center gap-3">
        <button
            type="button"
            @click="sidebarOpen = true"
            class="rounded-lg p-1.5 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 lg:hidden dark:text-mist-400 dark:hover:bg-ink-700"
            aria-label="Open sidebar"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M3.75 6.75h16.5M3.75 12h16.5m-16.5 5.25h16.5" />
            </svg>
        </button>

        <h1 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $title ?? 'Dashboard' }}</h1>
    </div>

    <div class="flex items-center gap-2">
        {{-- Appearance toggle: client-only preference, persisted to localStorage (docs/DESIGN_SYSTEM.md §2, ADR-15). --}}
        <button
            type="button"
            x-data
            @click="
                const isDark = document.documentElement.classList.toggle('dark');
                localStorage.setItem('veyra-theme', isDark ? 'dark' : 'light');
            "
            class="rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-700"
            aria-label="Toggle dark mode"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M21.752 15.002A9.72 9.72 0 0 1 18 15.75c-5.385 0-9.75-4.365-9.75-9.75 0-1.33.266-2.597.748-3.752A9.753 9.753 0 0 0 3 11.25C3 16.635 7.365 21 12.75 21a9.753 9.753 0 0 0 9.002-5.998Z" />
            </svg>
        </button>

        {{-- Notifications Drawer trigger --}}
        <button
            type="button"
            @click="notificationsOpen = true"
            class="rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-700"
            aria-label="Open notifications"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
        </button>

        <div class="flex items-center gap-3 border-s border-mist-200 ps-3 dark:border-ink-600">
            <div class="hidden text-end sm:block">
                <p class="text-sm font-medium text-ink-900 dark:text-ink-50">{{ auth()->user()->name }}</p>
                <p class="text-xs text-mist-500">{{ auth()->user()->tenant?->name }}</p>
            </div>

            <img
                src="{{ auth()->user()->avatar_url }}"
                alt="{{ auth()->user()->name }}"
                class="h-8 w-8 rounded-full border border-slate-700 object-cover"
            >

            <form method="POST" action="{{ route('logout') }}">
                @csrf
                <button
                    type="submit"
                    class="rounded-lg px-2 py-1.5 text-sm font-medium text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 hover:text-emerald-600 active:scale-[0.98] dark:text-mist-400 dark:hover:bg-ink-700 dark:hover:text-emerald-400"
                >
                    Logout
                </button>
            </form>
        </div>
    </div>
</header>
