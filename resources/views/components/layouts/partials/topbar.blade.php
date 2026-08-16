@props(['title' => null])

@php
    use App\Domain\Tenancy\Models\Attendance;

    $user = auth()->user();
    $user?->loadMissing('avatar');
    $userName = $user?->name ?? 'المستخدم';
    $userEmail = $user?->email ?? '';
    $tenantName = $user?->tenant?->name ?? 'مؤسستك';
    $avatarUrl = $user?->avatar_url;

    $employee = $user?->employee;
    $todayAttendance = $employee
        ? Attendance::query()
            ->where('employee_id', $employee->id)
            ->whereDate('date', now()->toDateString())
            ->first()
        : null;
    $showCheckOutButton = $todayAttendance?->check_in !== null && $todayAttendance?->check_out === null;
@endphp

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
            <h1 class="truncate font-display text-lg font-medium text-ink-900 dark:text-ink-50">{{ $title ?? 'لوحة التحكم' }}</h1>
            <p class="hidden truncate text-xs text-mist-500 sm:block dark:text-mist-400">{{ $tenantName }}</p>
        </div>
    </div>

    <div class="flex items-center gap-2">
        {{-- Topbar search (visual chrome aligned with Platform Console) --}}
        <div class="relative hidden md:block">
            <label for="tenant-topbar-search" class="sr-only">بحث في المنصة</label>
            <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                <path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" />
            </svg>
            <input
                id="tenant-topbar-search"
                type="search"
                name="q"
                placeholder="بحث في المنصة..."
                autocomplete="off"
                class="w-56 rounded-lg border border-mist-200 bg-white py-2 ps-9 pe-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 lg:w-72 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50"
            >
        </div>

        {{--
            Was gated on hr.my_space.view alongside a link to the retired
            My Space hub. Now gated on the ability it actually exercises, so the
            shortcut keeps working for anyone who can record their own attendance.
        --}}
        @can('hr.attendance.check_in_out')
            <a
                href="{{ route('tenant.hr.my-attendance') }}"
                wire:navigate
                class="hidden items-center gap-1.5 rounded-xl border border-brand-500/40 bg-brand-500/10 px-3 py-1.5 text-xs font-semibold text-brand-700 transition hover:bg-brand-500/20 sm:inline-flex dark:text-brand-300"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                    <path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />
                </svg>
                حضوري
            </a>

            @if ($showCheckOutButton)
                <form method="POST" action="{{ route('tenant.hr.attendance.checkout') }}">
                    @csrf
                    <button
                        type="submit"
                        class="hidden items-center gap-1.5 rounded-xl border border-amber-400/50 bg-amber-400/15 px-3 py-1.5 text-xs font-semibold text-amber-900 transition hover:bg-amber-400/25 sm:inline-flex dark:text-amber-200"
                        title="تسجيل الانصراف"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                        </svg>
                        تسجيل انصراف
                    </button>
                </form>
            @endif
        @endcan


        {{--
            Messenger.

            A link, not a drawer toggle: a conversation is a real URL, so
            routing here keeps the back button meaningful and lets a thread be
            linked to. The unread total is computed server-side per request —
            it is a single indexed count against the read watermarks, not a
            poll, and it refreshes on navigation like the rest of the chrome.
        --}}
        @php
            $messengerUnread = \App\Domain\Messaging\Models\ConversationParticipant::query()
                ->where('user_id', auth()->id())
                ->get()
                ->sum(fn ($participant): int => $participant->unreadCount());
        @endphp
        <a
            href="{{ route('tenant.messenger.index') }}"
            @class([
                'relative rounded-lg p-2 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:hover:bg-ink-800',
                'text-brand-600 dark:text-brand-300' => request()->routeIs('tenant.messenger.*'),
                'text-mist-500 dark:text-mist-400' => ! request()->routeIs('tenant.messenger.*'),
            ])
            aria-label="المراسلات"
            title="المراسلات"
            data-testid="tenant-messenger-link"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" />
            </svg>
            @if ($messengerUnread > 0)
                <span
                    class="absolute end-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-brand-500 px-1 text-xs font-bold text-white"
                    data-testid="tenant-messenger-badge"
                >{{ $messengerUnread > 99 ? '99+' : $messengerUnread }}</span>
            @endif
        </a>

        <button
            type="button"
            @click="openNotificationsDrawer()"
            class="relative rounded-lg p-2 text-mist-500 transition duration-200 ease-in-out hover:bg-mist-100 active:scale-90 dark:text-mist-400 dark:hover:bg-ink-800"
            aria-label="الإشعارات"
            title="الإشعارات"
            data-testid="tenant-notifications-bell"
        >
            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                <path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" />
            </svg>
            <span
                x-show="unreadCount > 0"
                x-cloak
                class="absolute end-1 top-1 flex h-4 min-w-4 items-center justify-center rounded-full bg-danger-solid px-1 text-xs font-bold text-white"
                x-text="unreadCount > 99 ? '99+' : unreadCount"
                data-testid="tenant-notifications-badge"
            ></span>
        </button>

        <div class="relative" @click.outside="profileOpen = false">
            <button
                type="button"
                @click="profileOpen = ! profileOpen"
                class="flex items-center gap-2 rounded-lg border-s border-mist-200 ps-2 transition duration-200 ease-in-out sm:ps-3 dark:border-ink-700"
                aria-label="قائمة الحساب"
                :aria-expanded="profileOpen.toString()"
            >
                <div class="hidden text-end sm:block">
                    <p class="text-sm font-medium text-ink-900 dark:text-ink-50">{{ $userName }}</p>
                    <p class="text-xs text-mist-500 dark:text-mist-400">{{ $tenantName }}</p>
                </div>
                @if ($avatarUrl)
                    <img
                        src="{{ $avatarUrl }}"
                        alt="{{ $userName }}"
                        class="h-8 w-8 rounded-full border border-slate-700 object-cover"
                    >
                @else
                    <span class="flex h-8 w-8 items-center justify-center rounded-md border border-slate-700 bg-brand-500/15 font-display text-sm font-medium text-brand-600 dark:text-brand-300">
                        {{ mb_substr($userName, 0, 1) }}
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
                        @if ($avatarUrl)
                            <img
                                src="{{ $avatarUrl }}"
                                alt="{{ $userName }}"
                                class="h-8 w-8 shrink-0 rounded-full border border-slate-700 object-cover"
                            >
                        @endif
                        <div class="min-w-0">
                            <p class="truncate text-sm font-medium text-ink-900 dark:text-ink-50">{{ $userName }}</p>
                            <p class="truncate text-xs text-mist-500 dark:text-mist-400">{{ $userEmail }}</p>
                        </div>
                    </div>
                </div>

                <a
                    href="{{ route('profile.edit') }}"
                    @click="profileOpen = false"
                    class="flex items-center gap-2 px-4 py-2 text-sm text-ink-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                        <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 6a3.75 3.75 0 1 1-7.5 0 3.75 3.75 0 0 1 7.5 0ZM4.501 20.118a7.5 7.5 0 0 1 14.998 0A17.933 17.933 0 0 1 12 21.75c-2.676 0-5.216-.584-7.499-1.632Z" />
                    </svg>
                    الملف الشخصي
                </a>

                @can('tenant.settings.view')
                    <a
                        href="{{ route('settings.company') }}"
                        wire:navigate
                        @click="profileOpen = false"
                        class="flex items-center gap-2 px-4 py-2 text-sm text-ink-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.174.1.347.223.52.337.294.191.66.237.983.094l1.22-.547a1.125 1.125 0 011.45.53l1.298 2.247a1.125 1.125 0 01-.26 1.431l-1.003.827c-.293.24-.438.613-.43.992a6.759 6.759 0 010 .255c-.008.378.137.75.43.99l1.005.828c.424.35.534.954.26 1.43l-1.298 2.247a1.125 1.125 0 01-1.45.533l-1.22-.547a1.125 1.125 0 00-.983.094 6.57 6.57 0 01-.52.337c-.332.183-.582.495-.645.87l-.213 1.28c-.09.543-.56.941-1.11.941h-2.594c-.55 0-1.02-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 01-.52-.337 1.125 1.125 0 00-.983-.094l-1.22.547a1.125 1.125 0 01-1.45-.53l-1.298-2.247a1.125 1.125 0 01.26-1.431l1.004-.827c.292-.24.437-.613.43-.992a6.932 6.932 0 010-.255c.007-.378-.138-.75-.43-.99l-1.004-.828a1.125 1.125 0 01-.26-1.43l1.298-2.247a1.125 1.125 0 011.45-.533l1.22.547c.323.144.69.098.983-.094.173-.114.346-.237.52-.337.332-.183.582-.495.644-.87l.214-1.281z" />
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 11-6 0 3 3 0 016 0z" />
                        </svg>
                        إعدادات المؤسسة
                    </a>
                @endcan

                <form method="POST" action="{{ route('logout') }}" class="border-t border-mist-100 dark:border-ink-700">
                    @csrf
                    <button type="submit" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-danger-solid transition hover:bg-danger-solid/10">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M15.75 9V5.25A2.25 2.25 0 0 0 13.5 3h-6a2.25 2.25 0 0 0-2.25 2.25v13.5A2.25 2.25 0 0 0 7.5 21h6a2.25 2.25 0 0 0 2.25-2.25V15M12 9l-3 3m0 0 3 3m-3-3h12.75" />
                        </svg>
                        تسجيل الخروج
                    </button>
                </form>
            </div>
        </div>
    </div>
</header>
