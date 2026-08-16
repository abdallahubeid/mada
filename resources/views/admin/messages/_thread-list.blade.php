@forelse ($threads as $thread)
    <div
        id="mada-search-thread-{{ $thread['id'] }}"
        data-thread-id="{{ $thread['id'] }}"
        data-mada-search="thread-{{ $thread['id'] }}"
        @class([
            'group relative flex gap-3 border-b border-mist-100 p-4 transition duration-150 dark:border-ink-700',
            'bg-brand-500/[0.06] border-s-2 border-s-brand-500' => $thread['is_selected'],
            'hover:bg-mist-50 dark:hover:bg-ink-700/40' => ! $thread['is_selected'],
        ])
    >
        <a href="{{ $thread['open_url'] }}" class="flex min-w-0 flex-1 gap-3">
            <img
                src="{{ $thread['avatar_url'] }}"
                alt="{{ $thread['display_name'] }}"
                class="h-10 w-10 shrink-0 rounded-full border border-slate-700 object-cover"
            >
            <div class="min-w-0 flex-1">
                <div class="flex items-center justify-between gap-2 pe-8">
                    <p class="truncate text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $thread['display_name'] }}</p>
                    <span
                        class="mada-relative-time shrink-0 text-xs text-mist-400 dark:text-mist-500"
                        data-timestamp="{{ $thread['last_message_at'] }}"
                    >
                        {{ $thread['last_message_at'] ? \Illuminate\Support\Carbon::parse($thread['last_message_at'])->diffForHumans() : '' }}
                    </span>
                </div>
                <p class="mt-0.5 truncate text-sm font-medium text-ink-700 dark:text-mist-200">{{ $thread['subject'] }}</p>
                <p class="mt-0.5 truncate text-xs text-mist-500 dark:text-mist-400">{{ $thread['snippet'] }}</p>
                <div class="mt-1.5 flex items-center gap-2">
                    <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium {{ $thread['status_badge'] }}">
                        <span class="h-1.5 w-1.5 rounded-full {{ $thread['status_dot'] }}"></span>
                        {{ $thread['status_label'] }}
                    </span>
                    @if ($thread['unread'])
                        <span class="h-2 w-2 rounded-full bg-brand-500"></span>
                    @endif
                </div>
            </div>
        </a>

        <div class="absolute end-2 top-3">
            <button
                type="button"
                @click.stop="toggleMenu({{ $thread['id'] }})"
                class="rounded-lg p-1.5 text-mist-400 opacity-0 transition hover:bg-mist-100 hover:text-ink-700 group-hover:opacity-100 dark:hover:bg-ink-700 dark:hover:text-mist-200"
                :class="openMenuId === {{ $thread['id'] }} && 'opacity-100'"
                aria-label="إجراءات المحادثة"
            >
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" /></svg>
            </button>
            <div
                x-show="openMenuId === {{ $thread['id'] }}"
                x-cloak
                @click.outside="openMenuId = null"
                x-transition
                class="absolute end-0 z-20 mt-1 w-40 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-xl dark:border-ink-600 dark:bg-ink-800"
            >
                @if ($thread['can_archive'])
                    <form method="POST" action="{{ $thread['archive_url'] }}">
                        @csrf
                        <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">
                            أرشفة
                        </button>
                    </form>
                @endif
                <button
                    type="button"
                    @click.stop="openMenuId = null; deleteThread(@js($thread))"
                    class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-danger-solid hover:bg-danger-solid/10"
                >
                    حذف
                </button>
            </div>
        </div>
    </div>
@empty
    <div class="p-8 text-center">
        <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد محادثات</p>
        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">لا توجد رسائل في هذه الحالة.</p>
    </div>
@endforelse
