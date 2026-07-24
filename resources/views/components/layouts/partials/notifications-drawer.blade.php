{{--
    Notifications slide-over shell (docs/DESIGN_SYSTEM.md §4, USER_JOURNEYS.md).
    This is chrome only for now — the real Notifications platform service
    (docs/MODULES.md §4) lands in a later Phase 1 slice.
--}}
<div
    x-show="notificationsOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="notificationsOpen = false"
    class="fixed inset-0 z-40 bg-ink-950/60"
></div>

<aside
    x-show="notificationsOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="translate-x-full rtl:-translate-x-full opacity-0"
    x-transition:enter-end="translate-x-0 opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="translate-x-0 opacity-100"
    x-transition:leave-end="translate-x-full rtl:-translate-x-full opacity-0"
    @click.outside="notificationsOpen = false"
    class="fixed inset-y-0 end-0 z-50 w-full max-w-sm border-s border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800"
>
    <div class="flex items-center justify-between">
        <h2 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">Notifications</h2>
        <button
            type="button"
            @click="notificationsOpen = false"
            class="rounded-lg p-1 text-mist-400 transition duration-200 ease-in-out hover:bg-mist-100 hover:text-mist-600 active:scale-90 dark:hover:bg-ink-700 dark:hover:text-white"
            aria-label="Close notifications"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
            </svg>
        </button>
    </div>

    <div class="mt-6 rounded-2xl border border-dashed border-mist-200 p-6 text-center dark:border-ink-600">
        <p class="text-sm font-medium text-ink-900 dark:text-ink-50">No notifications yet</p>
        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
            Leave approvals, new applicants, and payroll updates will show up here.
        </p>
    </div>
</aside>
