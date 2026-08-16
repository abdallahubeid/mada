{{-- Tenant Owner dual-channel notifications drawer (database + Reverb). --}}
<div
    x-show="notificationsOpen"
    x-cloak
    x-transition:enter="transition ease-out duration-300"
    x-transition:enter-start="opacity-0"
    x-transition:enter-end="opacity-100"
    x-transition:leave="transition ease-in duration-200"
    x-transition:leave-start="opacity-100"
    x-transition:leave-end="opacity-0"
    @click="closeNotificationsDrawer()"
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
    @click.outside="closeNotificationsDrawer()"
    @notifications-opened.window="loadNotifications()"
    class="fixed inset-y-0 end-0 z-50 flex w-full max-w-sm flex-col border-s border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800"
>
    <div class="flex items-center justify-between border-b border-mist-100 px-6 py-4 dark:border-ink-700">
        <div>
            <h2 class="font-display text-base font-medium text-ink-900 dark:text-ink-50">الإشعارات</h2>
            {{--
                Counts the rows still rendered unread in THIS view, not the
                badge. The badge is zeroed the moment the drawer opens, so
                binding to it here would announce "لا توجد غير مقروءة" over a
                list of items the user can plainly see are new.
            --}}
            <p
                class="text-xs text-mist-500"
                x-text="(() => {
                    const fresh = notifications.filter((item) => ! item.read_at).length;
                    return fresh > 0 ? (fresh + ' غير مقروء') : 'لا توجد غير مقروءة';
                })()"
            ></p>
        </div>
        {{--
            No manual "قراءة الكل" button: opening or dismissing the drawer is
            itself the acknowledgement (see acknowledgeAll() in the shell), so a
            button that repeats what already happened on open would never have
            anything left to do.
        --}}
        <div class="flex items-center gap-1">
            <button
                type="button"
                @click="closeNotificationsDrawer()"
                class="rounded-lg p-1 text-mist-400 transition duration-200 ease-in-out hover:bg-mist-100 hover:text-mist-600 active:scale-90 dark:hover:bg-ink-700 dark:hover:text-white"
                aria-label="إغلاق الإشعارات"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" />
                </svg>
            </button>
        </div>
    </div>

    <div class="min-h-0 flex-1 overflow-y-auto px-4 py-4">
        <template x-if="notificationsLoading">
            <p class="px-2 py-6 text-center text-sm text-mist-500">جاري التحميل…</p>
        </template>

        <template x-if="! notificationsLoading && notifications.length === 0">
            <div class="rounded-2xl border border-dashed border-mist-200 p-6 text-center dark:border-ink-600">
                <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد إشعارات بعد</p>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    طلبات الإجازة، المتقدمون الجدد، وتغييرات الأمان ستظهر هنا.
                </p>
            </div>
        </template>

        <ul class="space-y-2">
            <template x-for="item in notifications" :key="item.id">
                <li>
                    <a
                        :href="item.url || '#'"
                        @click.prevent="openNotification(item)"
                        class="block rounded-xl border px-3 py-3 transition hover:bg-mist-50 dark:hover:bg-ink-700"
                        :class="item.read_at
                            ? 'border-mist-100 dark:border-ink-700'
                            : 'border-brand-500/40 bg-brand-500/5 dark:border-brand-500/30'"
                    >
                        <div class="flex items-start justify-between gap-2">
                            <p class="text-sm font-semibold text-ink-900 dark:text-ink-50" x-text="item.title"></p>
                            <span
                                x-show="! item.read_at"
                                class="mt-1 h-2 w-2 shrink-0 rounded-full bg-brand-500"
                            ></span>
                        </div>
                        <p class="mt-1 text-xs leading-5 text-mist-500 dark:text-mist-400" x-text="item.message"></p>
                    </a>
                </li>
            </template>
        </ul>
    </div>
</aside>
