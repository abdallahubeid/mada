@php
    $isArchivedFolder = ($folder ?? 'active') === 'archived';
@endphp

@forelse ($threads as $thread)
    <div
        data-thread-id="{{ $thread['id'] }}"
        data-show-url="{{ $thread['show_url'] }}"
        data-archive-url="{{ $thread['archive_url'] }}"
        data-unarchive-url="{{ $thread['unarchive_url'] }}"
        data-destroy-url="{{ $thread['destroy_url'] }}"
        data-is-archived="{{ ($thread['is_archived'] ?? false) ? '1' : '0' }}"
        @class([
            'group relative flex gap-3 border-b border-mist-100 p-4 transition duration-150 dark:border-ink-700',
            'bg-brand-500/[0.06] border-s-2 border-s-brand-500' => $thread['is_selected'],
            'hover:bg-mist-50 dark:hover:bg-ink-700/40' => ! $thread['is_selected'],
        ])
    >
        <button
            type="button"
            class="flex min-w-0 flex-1 gap-3 text-start"
            @click="selectThread({{ $thread['id'] }}, @js($thread['show_url']))"
        >
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
                <p class="mt-0.5 truncate text-xs text-mist-500 dark:text-mist-400" data-thread-snippet>{{ $thread['snippet'] }}</p>
                <div class="mt-1.5 flex items-center gap-2">
                    @include('tenant.contact-messages._receipt', ['status' => $thread['receipt'], 'onDark' => false])
                    @if ($thread['unread'])
                        <span data-thread-unread class="inline-flex min-w-5 items-center justify-center rounded-full bg-brand-500 px-1.5 text-xs font-bold text-white">
                            {{ max(1, (int) ($thread['unread_count'] ?? 1)) }}
                        </span>
                    @endif
                </div>
            </div>
        </button>

        @if ($canManage ?? false)
            <div class="absolute end-2 top-3" @click.stop>
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
                    class="absolute end-0 z-20 mt-1 w-48 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-xl dark:border-ink-600 dark:bg-ink-800"
                >
                    @if ($isArchivedFolder || ($thread['is_archived'] ?? false))
                        <button
                            type="button"
                            @click.stop="openMenuId = null; unarchiveThread({{ $thread['id'] }}, @js($thread['unarchive_url']))"
                            class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M9 15 3 9m0 0 6-6M3 9h12a6 6 0 0 1 0 12h-3" /></svg>
                            إلغاء الأرشفة
                        </button>
                    @else
                        <button
                            type="button"
                            @click.stop="openMenuId = null; archiveThread({{ $thread['id'] }}, @js($thread['archive_url']))"
                            class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5M10 11.25h4M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                            أرشفة
                        </button>
                    @endif
                    <button
                        type="button"
                        @click.stop="openMenuId = null; deleteThread({{ $thread['id'] }}, @js($thread['destroy_url']))"
                        class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-danger-solid hover:bg-danger-solid/10"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0V4.306c0-.846-.694-1.528-1.54-1.486a48.64 48.64 0 0 0-3.92 0c-.846-.042-1.54.64-1.54 1.486V5.79m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                        حذف
                    </button>
                </div>
            </div>
        @endif
    </div>
@empty
    <div class="p-8 text-center" data-thread-empty>
        <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد محادثات</p>
        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
            {{ ($folder ?? 'active') === 'archived' ? 'لا توجد محادثات في الأرشيف.' : 'ستظهر رسائل نموذج التواصل هنا تلقائيًا.' }}
        </p>
    </div>
@endforelse
