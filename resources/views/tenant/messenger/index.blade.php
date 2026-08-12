<x-layouts.app title="المراسلات">
    {{--
        Two panes on desktop, one at a time on mobile.

        The mobile behaviour is expressed as `hidden lg:flex` / `flex lg:flex`
        driven by whether a thread is open, rather than as a JS router: a
        conversation is a real URL, so the browser back button walks out of a
        thread and into the list without any state to keep in sync.
    --}}
    <div
        x-data="veyraMessenger(@js([
            'conversationId' => $activeConversation?->id,
            'tenantId' => auth()->user()->tenant_id,
            'userId' => auth()->id(),
            'userName' => auth()->user()->name,
            'sendUrlTemplate' => route('tenant.messenger.send', ['conversation' => '__ID__']),
            'pulseUrlTemplate' => route('tenant.messenger.pulse', ['conversation' => '__ID__']),
            'indexUrl' => route('tenant.messenger.index'),
            'peerStatus' => $peerStatus,
            'readWatermark' => $readWatermark,
        ]))"
        x-init="boot()"
        {{-- Esc backs out one layer at a time: an in-progress reply first, the
             thread only once there is nothing else to cancel. Bound on window
             rather than the pane so it works wherever focus happens to be, and
             guarded on there being an open thread so it is inert on the list
             view. The menus and popovers stop the event themselves. --}}
        @keydown.window.escape="onEscape()"
        class="flex h-[calc(100vh-8.5rem)] gap-4"
    >
        {{-- Pane 1 — conversations + directory --}}
        <aside @class([
            'w-full shrink-0 flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white lg:flex lg:w-80 dark:border-ink-600 dark:bg-ink-800',
            'hidden' => $activeConversation !== null,
            'flex' => $activeConversation === null,
        ])>
            <div class="flex items-start justify-between gap-2 border-b border-mist-100 px-4 py-3.5 dark:border-ink-700">
                <div>
                    <h2 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">المراسلات</h2>
                    <p class="mt-0.5 text-xs text-mist-500 dark:text-mist-400">محادثات خاصة بينك وبين زملائك</p>
                </div>
                <button
                    type="button"
                    @click="$dispatch('open-chat-privacy')"
                    class="rounded-lg p-1.5 text-mist-500 transition hover:bg-mist-100 dark:text-mist-400 dark:hover:bg-ink-700"
                    aria-label="إعدادات الخصوصية"
                    title="إعدادات الخصوصية"
                    data-testid="messenger-privacy"
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9.594 3.94c.09-.542.56-.94 1.11-.94h2.593c.55 0 1.02.398 1.11.94l.213 1.281c.063.374.313.686.645.87.074.04.147.083.22.127.325.196.72.257 1.075.124l1.217-.456a1.125 1.125 0 0 1 1.37.49l1.296 2.247a1.125 1.125 0 0 1-.26 1.431l-1.003.827c-.293.241-.438.613-.43.992a7.03 7.03 0 0 1 0 .255c-.008.378.137.75.43.991l1.004.827c.424.35.534.955.26 1.43l-1.298 2.247a1.125 1.125 0 0 1-1.369.491l-1.217-.456c-.355-.133-.75-.072-1.076.124a6.47 6.47 0 0 1-.22.128c-.331.183-.581.495-.644.869l-.213 1.281c-.09.543-.56.94-1.11.94h-2.594c-.55 0-1.019-.398-1.11-.94l-.213-1.281c-.062-.374-.312-.686-.644-.87a6.52 6.52 0 0 1-.22-.127c-.325-.196-.72-.257-1.076-.124l-1.217.456a1.125 1.125 0 0 1-1.369-.49l-1.297-2.247a1.125 1.125 0 0 1 .26-1.431l1.004-.827c.292-.24.437-.613.43-.991a6.932 6.932 0 0 1 0-.255c.007-.38-.138-.751-.43-.992l-1.004-.827a1.125 1.125 0 0 1-.26-1.43l1.297-2.247a1.125 1.125 0 0 1 1.37-.491l1.216.456c.356.133.751.072 1.076-.124.072-.044.146-.086.22-.128.332-.183.582-.495.644-.869l.214-1.28Z" /><path stroke-linecap="round" stroke-linejoin="round" d="M15 12a3 3 0 1 1-6 0 3 3 0 0 1 6 0Z" /></svg>
                </button>
            </div>

            {{-- Directory picker: starting a thread is a POST, so it is a form
                 rather than a link — a GET that creates a row would be
                 followed by every prefetcher and crawler. --}}
            <div class="border-b border-mist-100 p-3 dark:border-ink-700">
                <div x-data="{ open: false, q: '' }" class="relative">
                    <button
                        type="button"
                        @click="open = ! open"
                        class="flex w-full items-center justify-center gap-2 rounded-xl bg-emerald-400 px-3 py-2 text-sm font-semibold text-emerald-950 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-[0.98]"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M12 4.5v15m7.5-7.5h-15" /></svg>
                        محادثة جديدة
                    </button>

                    {{-- Group creation is the ONE messaging capability behind a
                         permission, so the trigger only renders for holders.
                         An Employee never sees a control they would be refused
                         at the route. --}}
                    @if ($canCreateGroups)
                        <button
                            type="button"
                            @click="$dispatch('open-group-modal')"
                            class="mt-2 flex w-full items-center justify-center gap-2 rounded-xl border border-mist-200 px-3 py-2 text-sm font-semibold text-ink-700 transition duration-200 hover:border-emerald-400 hover:text-emerald-700 dark:border-ink-600 dark:text-mist-200 dark:hover:text-emerald-400"
                            data-testid="messenger-new-group"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M18 18.72a9.094 9.094 0 0 0 3.741-.479 3 3 0 0 0-4.682-2.72m.94 3.198.001.031c0 .225-.012.447-.037.666A11.944 11.944 0 0 1 12 21c-2.17 0-4.207-.576-5.963-1.584A6.062 6.062 0 0 1 6 18.719m12 0a5.971 5.971 0 0 0-.941-3.197m0 0A5.995 5.995 0 0 0 12 12.75a5.995 5.995 0 0 0-5.058 2.772m0 0a3 3 0 0 0-4.681 2.72 8.986 8.986 0 0 0 3.74.477m.94-3.197a5.971 5.971 0 0 0-.94 3.197M15 6.75a3 3 0 1 1-6 0 3 3 0 0 1 6 0Zm6 3a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Zm-13.5 0a2.25 2.25 0 1 1-4.5 0 2.25 2.25 0 0 1 4.5 0Z" /></svg>
                            مجموعة جديدة
                        </button>
                    @endif

                    <div x-show="open" x-cloak @click.outside="open = false" class="absolute inset-x-0 top-full z-20 mt-2 overflow-hidden rounded-xl border border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-900">
                        <input
                            type="search"
                            x-model="q"
                            placeholder="ابحث عن زميل..."
                            class="w-full border-b border-mist-100 bg-transparent px-3 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:outline-none dark:border-ink-700 dark:text-ink-50"
                        >
                        <div class="max-h-64 overflow-y-auto">
                            @forelse ($directory as $person)
                                {{-- Was a form POST + 302. Now a fetch that
                                     ends in Livewire.navigate, so opening a new
                                     thread does not repaint the console. --}}
                                <div x-show="q === '' || @js($person['name']).includes(q) || @js((string) ($person['department'] ?? '')).includes(q)">
                                    <button type="button" @click="open = false; startThread({{ $person['user_id'] }})" class="flex w-full items-center gap-3 px-3 py-2.5 text-start transition hover:bg-mist-50 dark:hover:bg-ink-800">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-xs font-bold text-emerald-800 dark:text-emerald-400">{{ $person['initial'] }}</span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-medium text-ink-900 dark:text-ink-50">{{ $person['name'] }}</span>
                                            <span class="block truncate text-xs text-mist-500 dark:text-mist-400">{{ $person['job_title'] ?? $person['department'] ?? '—' }}</span>
                                        </span>
                                    </button>
                                </div>
                            @empty
                                {{-- Employees without a linked account are hidden
                                     entirely, so an empty directory is a real
                                     state and needs to explain itself. --}}
                                <p class="px-3 py-6 text-center text-xs text-mist-500 dark:text-mist-400">
                                    لا يوجد زملاء لديهم حسابات مفعّلة بعد.
                                </p>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>

            <div class="min-h-0 flex-1 overflow-y-auto">
                @forelse ($conversations as $thread)
                    {{-- group/card so the ... reveals on hover of the row, and
                         `relative` so its dropdown anchors to the card. --}}
                    <div data-card="{{ $thread['id'] }}" @class([
                        'group/card relative flex items-center gap-2 border-b border-mist-100 pe-2 transition duration-200 dark:border-ink-700/60',
                        'bg-emerald-400/10' => $activeConversation?->id === $thread['id'],
                        'hover:bg-mist-50 dark:hover:bg-ink-700/40' => $activeConversation?->id !== $thread['id'],
                    ])>
                        {{-- wire:navigate: switching threads swaps the body
                             without a document load, so the sidebar, the theme
                             class and the open socket all survive. --}}
                        <a href="{{ route('tenant.messenger.show', $thread['id']) }}" wire:navigate class="flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
                            {{-- `relative` hosts the presence dot. --}}
                            <span class="relative shrink-0">
                                <span class="flex h-10 w-10 items-center justify-center rounded-full bg-mist-100 font-display text-sm font-bold text-mist-600 dark:bg-ink-700 dark:text-mist-300">{{ $thread['initial'] }}</span>
                                @if ($thread['peer_online'] === true)
                                    {{-- Rendered only for a definite yes. A peer
                                         who hid their presence returns null and
                                         is indistinguishable from one who is
                                         simply offline — which is the promise
                                         the privacy toggle makes. --}}
                                    <span
                                        class="absolute bottom-0 end-0 h-3 w-3 rounded-full border-2 border-white bg-emerald-500 dark:border-ink-800"
                                        title="متصل الآن"
                                        aria-label="متصل الآن"
                                        data-testid="messenger-online-dot"
                                    ></span>
                                @endif
                            </span>
                            <span class="min-w-0 flex-1">
                                <span class="flex items-center justify-between gap-2">
                                    <span class="truncate text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $thread['title'] }}</span>
                                    @if ($thread['unread'] > 0)
                                        <span class="shrink-0 rounded-full bg-emerald-400 px-1.5 py-0.5 text-[10px] font-bold text-emerald-950">{{ $thread['unread'] }}</span>
                                    @endif
                                </span>
                                <span class="mt-0.5 block truncate text-xs text-mist-500 dark:text-mist-400">{{ $thread['last_message_at'] ?? 'لم تبدأ المحادثة بعد' }}</span>
                            </span>
                        </a>

                        {{-- Options, per card. `@click.stop` on the trigger so
                             opening the menu does not also follow the card's
                             link into the thread. --}}
                        <div class="relative shrink-0" x-data="{ open: false }">
                            <button
                                type="button"
                                @click.stop.prevent="open = ! open"
                                class="rounded-lg p-1.5 text-mist-400 opacity-0 transition hover:bg-mist-100 hover:text-ink-700 focus-visible:opacity-100 group-hover/card:opacity-100 dark:hover:bg-ink-700 dark:hover:text-mist-100"
                                :class="open && 'opacity-100'"
                                aria-label="خيارات المحادثة"
                                title="خيارات"
                                data-testid="messenger-thread-menu"
                            >
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 12.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5ZM12 18.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" /></svg>
                            </button>

                            <div x-show="open" x-cloak @click.outside="open = false" class="absolute end-0 top-full z-30 mt-1 w-56 overflow-hidden rounded-xl border border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-900">
                                <button type="button" @click.stop="archive({{ $thread['id'] }}); open = false" class="flex w-full items-center gap-2 px-4 py-2.5 text-start text-sm text-ink-700 transition hover:bg-mist-50 dark:text-mist-200 dark:hover:bg-ink-800">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m20.25 7.5-.625 10.632a2.25 2.25 0 0 1-2.247 2.118H6.622a2.25 2.25 0 0 1-2.247-2.118L3.75 7.5m8.25 3v6.75m0 0-3-3m3 3 3-3M3.375 7.5h17.25c.621 0 1.125-.504 1.125-1.125v-1.5c0-.621-.504-1.125-1.125-1.125H3.375c-.621 0-1.125.504-1.125 1.125v1.5c0 .621.504 1.125 1.125 1.125Z" /></svg>
                                    أرشفة المحادثة
                                </button>
                                <button type="button" @click.stop="hideThread({{ $thread['id'] }}); open = false" class="flex w-full items-center gap-2 border-t border-mist-100 px-4 py-2.5 text-start text-sm text-danger-solid transition hover:bg-danger-solid/10 dark:border-ink-700">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m14.74 9-.346 9m-4.788 0L9.26 9m9.968-3.21c.342.052.682.107 1.022.166m-1.022-.165L18.16 19.673a2.25 2.25 0 0 1-2.244 2.077H8.084a2.25 2.25 0 0 1-2.244-2.077L4.772 5.79m14.456 0a48.108 48.108 0 0 0-3.478-.397m-12 .562c.34-.059.68-.114 1.022-.165m0 0a48.11 48.11 0 0 1 3.478-.397m7.5 0v-.916c0-1.18-.91-2.164-2.09-2.201a51.964 51.964 0 0 0-3.32 0c-1.18.037-2.09 1.022-2.09 2.201v.916m7.5 0a48.667 48.667 0 0 0-7.5 0" /></svg>
                                    حذف المحادثة
                                </button>
                                <p class="border-t border-mist-100 px-4 py-2 text-[10px] leading-relaxed text-mist-500 dark:border-ink-700 dark:text-mist-400">
                                    الحذف يزيلها من قائمتك أنت فقط — لا تُحذف نسخة الطرف الآخر.
                                </p>
                            </div>
                        </div>
                    </div>
                @empty
                    <p class="px-4 py-10 text-center text-sm text-mist-500 dark:text-mist-400">لا توجد محادثات بعد.</p>
                @endforelse
            </div>
        </aside>

        {{-- Pane 2 — the thread --}}
        <section @class([
            'min-w-0 flex-1 flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white lg:flex dark:border-ink-600 dark:bg-ink-800',
            'flex' => $activeConversation !== null,
            'hidden' => $activeConversation === null,
        ])>
            @if ($activeConversation === null)
                <div class="flex h-full flex-col items-center justify-center px-6 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-ink-700 dark:text-mist-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M8.625 12a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H8.25m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0H12m4.125 0a.375.375 0 1 1-.75 0 .375.375 0 0 1 .75 0Zm0 0h-.375M21 12c0 4.556-4.03 8.25-9 8.25a9.764 9.764 0 0 1-2.555-.337A5.972 5.972 0 0 1 5.41 20.97a5.969 5.969 0 0 1-.474-.065 4.48 4.48 0 0 0 .978-2.025c.09-.457-.133-.901-.467-1.226C3.93 16.178 3 14.189 3 12c0-4.556 4.03-8.25 9-8.25s9 3.694 9 8.25Z" /></svg>
                    </span>
                    <h3 class="mt-4 font-display text-base font-semibold text-ink-900 dark:text-ink-50">اختر محادثة للبدء</h3>
                    <p class="mt-1 max-w-sm text-sm text-mist-500 dark:text-mist-400">
                        محادثاتك خاصة بينك وبين المشاركين فيها فقط — لا يطّلع عليها المدير أو إدارة الموارد البشرية.
                    </p>
                </div>
            @else
                <header class="flex items-center gap-3 border-b border-mist-100 px-4 py-3 dark:border-ink-700">
                    <a href="{{ route('tenant.messenger.index') }}" wire:navigate class="rounded-lg p-1.5 text-mist-500 transition hover:bg-mist-100 lg:hidden dark:hover:bg-ink-700" aria-label="رجوع">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    </a>
                    <span class="relative shrink-0">
                        <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-400/15 font-display text-sm font-bold text-emerald-800 dark:text-emerald-400">{{ mb_substr($activeTitle, 0, 1) }}</span>
                        <span
                            x-show="peer.visible && peer.online"
                            x-cloak
                            class="absolute bottom-0 end-0 h-2.5 w-2.5 rounded-full border-2 border-white bg-emerald-500 dark:border-ink-800"
                            aria-hidden="true"
                        ></span>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $activeTitle }}</p>
                        {{--
                            Presence line. Three states, and the fallback is the
                            honest one: when the peer has hidden their presence
                            — or the thread is a group — this says nothing about
                            them at all rather than guessing.

                            The value is computed server-side by
                            ConversationPresence, so the browser is never sent a
                            timestamp it is not allowed to show.
                        --}}
                        <p
                            class="truncate text-xs"
                            :class="peer.visible && peer.online
                                ? 'font-medium text-emerald-700 dark:text-emerald-400'
                                : 'text-mist-500 dark:text-mist-400'"
                            x-text="peer.visible && peer.label ? peer.label : @js($activeConversation->isGroup() ? 'مجموعة' : 'محادثة خاصة')"
                            data-testid="messenger-presence"
                        >محادثة خاصة</p>
                    </div>

                    {{-- The options menu lives on each SIDEBAR CARD, not here:
                         archiving or deleting from inside the open thread means
                         acting on the thread you are reading and then being
                         thrown out of it. On the card, the target of the action
                         is the thing you are pointing at. --}}

                    {{-- Close. Esc does the same, bound on the root below. --}}
                    <a href="{{ route('tenant.messenger.index') }}" wire:navigate class="shrink-0 rounded-lg p-2 text-mist-500 transition hover:bg-mist-100 hover:text-rose-600 dark:text-mist-400 dark:hover:bg-ink-700 dark:hover:text-rose-400" aria-label="إغلاق المحادثة" title="إغلاق (Esc)" data-testid="messenger-close">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </a>
                </header>

                {{-- Pinned bar. Only rendered when a pin exists — an empty bar
                     would take vertical space from the thread permanently. --}}
                {{-- Always rendered, hidden when empty: pinning has to be able
                     to reveal this bar without a reload, and a bar that only
                     exists in the markup when a pin already exists cannot be. --}}
                <div
                    data-pinned-bar
                    data-pinned-id="{{ $pinnedMessage?->id }}"
                    @class([
                        'flex items-start gap-2 border-b border-emerald-400/30 bg-emerald-400/10 px-4 py-2',
                        'hidden' => $pinnedMessage === null,
                    ])
                >
                    <svg xmlns="http://www.w3.org/2000/svg" class="mt-0.5 h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2Z" /></svg>
                    <div class="min-w-0 flex-1">
                        <p class="text-[10px] font-semibold text-emerald-700 dark:text-emerald-400">رسالة مثبّتة</p>
                        <p data-pinned-text class="truncate text-xs text-ink-700 dark:text-mist-200">{{ $pinnedMessage?->body }}</p>
                    </div>
                    <button type="button" @click="unpin()" class="shrink-0 rounded p-1 text-mist-500 transition hover:text-danger-solid" aria-label="إلغاء التثبيت" title="إلغاء التثبيت">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div x-ref="scroll" class="min-h-0 flex-1 space-y-2 overflow-y-auto px-4 py-4">
                    @foreach ($messages as $message)
                        @php
                            $mine = $message->sender_id === auth()->id();
                            $counts = $message->reactions->groupBy('emoji')->map->count();
                            // Null when the quoted message was deleted: the
                            // relation carries the soft-delete scope, so a
                            // reply to a removed message quietly loses its
                            // quote rather than rendering an empty one.
                            $parent = $message->parent;
                        @endphp

                        @if ($message->isSystem())
                            {{-- The thread narrating itself: centred, no bubble,
                                 no reactions, no reply affordance. --}}
                            <p class="py-1 text-center text-[11px] text-mist-500 dark:text-mist-400">{{ $message->body }}</p>
                        @else
                            {{-- `quick`/`menu`/`up` live on the ROW, not inside
                                 the action cluster, so the bubble's right-click
                                 can open the same menu — see the contract note
                                 in the message-menu component. --}}
                            <div
                                x-data="{ quick: false, menu: false, up: true }"
                                data-message="{{ $message->id }}"
                                data-author="{{ $message->sender?->name ?? 'مستخدم محذوف' }}"
                                @class(['group flex items-end gap-1.5 transition', 'flex-row-reverse' => $mine])
                            >
                                {{-- relative + pb-1: the reaction pill hangs
                                     off the bubble's outer corner, so the
                                     bubble needs to be the positioning context
                                     and needs a little room beneath it. --}}
                                <div
                                    @contextmenu.prevent="quick = false; up = placeMenu($event.currentTarget); menu = true"
                                    @class([
                                        'relative max-w-[75%] rounded-2xl px-3.5 pb-3 pt-2 text-sm leading-relaxed',
                                        'bg-emerald-400 text-emerald-950' => $mine,
                                        'bg-mist-100 text-ink-800 dark:bg-ink-700 dark:text-mist-100' => ! $mine,
                                    ])
                                >
                                    {{-- Quote block. Colours are per bubble and
                                         carry NO text opacity: the emerald
                                         bubble is already a mid tone, and
                                         fading text on it was how the 55 AA
                                         failures on the landing page happened.
                                         Hierarchy comes from weight instead. --}}
                                    @if ($parent !== null)
                                        <button
                                            type="button"
                                            @click="jumpTo({{ $parent->id }})"
                                            @class([
                                                'mb-1.5 block w-full rounded-lg border-s-2 px-2 py-1 text-start transition duration-150 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-offset-1',
                                                'border-emerald-900 bg-emerald-900/10 hover:bg-emerald-900/20 focus-visible:ring-emerald-900 focus-visible:ring-offset-emerald-400' => $mine,
                                                'border-emerald-500 bg-black/5 hover:bg-black/10 focus-visible:ring-emerald-500 dark:bg-white/5 dark:hover:bg-white/10' => ! $mine,
                                            ])
                                            title="الانتقال إلى الرسالة الأصلية"
                                        >
                                            <span @class([
                                                'block truncate text-[10px] font-bold',
                                                'text-emerald-900' => $mine,
                                                'text-emerald-700 dark:text-emerald-400' => ! $mine,
                                            ])>{{ $parent->sender?->name ?? 'مستخدم محذوف' }}</span>
                                            <span @class([
                                                'block truncate text-xs',
                                                'text-emerald-900' => $mine,
                                                'text-ink-700 dark:text-mist-300' => ! $mine,
                                            ])>{{ \Illuminate\Support\Str::limit((string) $parent->body, 90) }}</span>
                                        </button>
                                    @endif

                                    {{-- A lone emoji IS the message: rendered
                                         oversized and unboxed, per convention. --}}
                                    <p
                                        data-body="{{ $message->id }}"
                                        @class([
                                            'whitespace-pre-line break-words',
                                            'text-4xl leading-tight' => $message->isLoneEmoji(),
                                        ])
                                    >{{ $message->body }}</p>

                                    <x-messenger.attachments
                                        :items="$message->attachments"
                                        :mine="$mine ? 'true' : 'false'"
                                    />

                                    <p @class([
                                        'mt-1 flex items-center gap-1 text-[10px]',
                                        'text-emerald-900/70' => $mine,
                                        'text-mist-500 dark:text-mist-400' => ! $mine,
                                    ])>
                                        <span>{{ $message->sent_at?->format('H:i') }}</span>
                                        @if ($mine)
                                            <x-messenger.read-ticks :id="$message->id" />
                                        @endif
                                    </p>

                                    {{-- Reaction pills. Floated onto the outer
                                         corner rather than boxed inside the
                                         bubble — a light chip on an emerald
                                         bubble read as a nested box. Dark glass
                                         reads the same on both bubble colours,
                                         so one treatment serves sender and
                                         recipient. --}}
                                    <div
                                        data-reactions="{{ $message->id }}"
                                        @class([
                                            'absolute -bottom-2.5 z-10 flex flex-wrap gap-1',
                                            'start-2' => $mine,
                                            'end-2' => ! $mine,
                                            'hidden' => $counts->isEmpty(),
                                        ])
                                    >
                                        @foreach ($counts as $emoji => $total)
                                            <span class="flex items-center gap-1 rounded-full border border-ink-600/50 bg-ink-800/90 px-1.5 py-0.5 text-xs text-mist-200 shadow-sm backdrop-blur-sm">{{ $emoji }} {{ $total }}</span>
                                        @endforeach
                                    </div>
                                </div>

                                <x-messenger.message-menu
                                    id="{{ $message->id }}"
                                    mine="{{ $mine ? 'true' : 'false' }}"
                                    :palette="$reactionPalette"
                                />
                            </div>
                        @endif
                    @endforeach

                    {{--
                        Messages arriving over Reverb — and my own, appended
                        optimistically — are rendered here.

                        Structurally identical to a server-rendered bubble,
                        including the full action cluster, because the
                        alternative is that a message you just sent has no
                        controls on it until the page is reloaded. The menu
                        component takes JS expressions precisely so the two
                        paths can share one definition.
                    --}}
                    <template x-for="m in live" :key="m.id">
                        <div
                            x-data="{ quick: false, menu: false, up: true }"
                            :data-message="m.id"
                            :data-author="m.sender_name"
                            class="group flex items-end gap-1.5 transition"
                            :class="m.sender_id === config.userId && 'flex-row-reverse'"
                        >
                            <div
                                @contextmenu.prevent="quick = false; up = placeMenu($event.currentTarget); menu = true"
                                class="relative max-w-[75%] rounded-2xl px-3.5 pb-3 pt-2 text-sm leading-relaxed"
                                :class="m.sender_id === config.userId
                                    ? 'bg-emerald-400 text-emerald-950'
                                    : 'bg-mist-100 text-ink-800 dark:bg-ink-700 dark:text-mist-100'"
                            >
                                <button
                                    x-show="m.quote"
                                    x-cloak
                                    type="button"
                                    @click="jumpTo(m.parent_id)"
                                    class="mb-1.5 block w-full rounded-lg border-s-2 px-2 py-1 text-start transition duration-150 focus-visible:outline-none"
                                    :class="m.sender_id === config.userId
                                        ? 'border-emerald-900 bg-emerald-900/10 hover:bg-emerald-900/20'
                                        : 'border-emerald-500 bg-black/5 hover:bg-black/10 dark:bg-white/5 dark:hover:bg-white/10'"
                                    title="الانتقال إلى الرسالة الأصلية"
                                >
                                    <span
                                        class="block truncate text-[10px] font-bold"
                                        :class="m.sender_id === config.userId ? 'text-emerald-900' : 'text-emerald-700 dark:text-emerald-400'"
                                        x-text="m.quote?.author"
                                    ></span>
                                    <span
                                        class="block truncate text-xs"
                                        :class="m.sender_id === config.userId ? 'text-emerald-900' : 'text-ink-700 dark:text-mist-300'"
                                        x-text="m.quote?.excerpt"
                                    ></span>
                                </button>

                                <p :data-body="m.id" class="whitespace-pre-line break-words" x-text="m.body"></p>

                                <x-messenger.attachments
                                    expr="m.attachments"
                                    mine="m.sender_id === config.userId"
                                />

                                <p
                                    class="mt-1 flex items-center gap-1 text-[10px]"
                                    :class="m.sender_id === config.userId ? 'text-emerald-900/70' : 'text-mist-500 dark:text-mist-400'"
                                >
                                    <span x-text="clock(m.sent_at)"></span>
                                    <template x-if="m.sender_id === config.userId">
                                        <x-messenger.read-ticks id="m.id" />
                                    </template>
                                </p>

                                {{-- Empty until `renderReactions` fills it, and
                                     carrying the same `flex … hidden` pair the
                                     server-rendered pill host uses so the one
                                     patch function serves both. --}}
                                <div
                                    :data-reactions="m.id"
                                    class="absolute -bottom-2.5 z-10 flex hidden flex-wrap gap-1"
                                    :class="m.sender_id === config.userId ? 'start-2' : 'end-2'"
                                ></div>
                            </div>

                            <x-messenger.message-menu
                                id="m.id"
                                mine="m.sender_id === config.userId"
                                :palette="$reactionPalette"
                            />
                        </div>
                    </template>
                </div>

                {{-- The composer is a column now: reply bar on top, controls
                     beneath. Both are inside the same <form> so Enter submits
                     the reply the bar is describing. --}}
                <form @submit.prevent="send()" class="border-t border-mist-100 dark:border-ink-700">
                    <x-messenger.reply-bar />

                    {{-- Pending files, before send. Shown as removable chips
                         rather than a count, so it is obvious WHAT is attached
                         and each one can be dropped without clearing the lot. --}}
                    <div x-show="pending.length > 0" x-cloak class="flex flex-wrap gap-2 border-b border-mist-100 px-3 py-2 dark:border-ink-700" data-testid="messenger-pending-files">
                        <template x-for="(file, index) in pending" :key="file.key">
                            <span class="flex max-w-[14rem] items-center gap-2 rounded-xl border border-mist-200 bg-mist-50 px-2.5 py-1.5 dark:border-ink-600 dark:bg-ink-900">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-emerald-700 dark:text-emerald-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M19.5 14.25v-2.625a3.375 3.375 0 0 0-3.375-3.375h-1.5A1.125 1.125 0 0 1 13.5 7.125v-1.5a3.375 3.375 0 0 0-3.375-3.375H8.25m2.25 0H5.625c-.621 0-1.125.504-1.125 1.125v17.25c0 .621.504 1.125 1.125 1.125h12.75c.621 0 1.125-.504 1.125-1.125V11.25a9 9 0 0 0-9-9Z" /></svg>
                                <span class="min-w-0">
                                    <span class="block truncate text-xs font-medium text-ink-800 dark:text-mist-100" x-text="file.file.name"></span>
                                    <span class="block text-[10px] text-mist-500 dark:text-mist-400" x-text="humanSize(file.file.size)"></span>
                                </span>
                                <button
                                    type="button"
                                    @click="pending.splice(index, 1)"
                                    class="shrink-0 rounded p-0.5 text-mist-500 transition hover:text-rose-600 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 dark:hover:text-rose-400"
                                    :aria-label="'إزالة ' + file.file.name"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                            </span>
                        </template>
                    </div>

                    <div class="flex items-end gap-2 p-3">
                    {{-- Live now: a private disk, an upload path and two
                         membership-checked serving routes all exist. The input
                         is hidden and driven by the button so the control keeps
                         the composer's styling rather than the browser's. --}}
                    <input
                        type="file"
                        x-ref="files"
                        multiple
                        class="hidden"
                        accept="{{ \App\Domain\Messaging\Support\MessageAttachmentStorage::acceptAttribute() }}"
                        @change="addFiles($event.target.files); $event.target.value = ''"
                    >
                    <button
                        type="button"
                        @click="$refs.files.click()"
                        title="إرفاق ملف"
                        class="shrink-0 rounded-xl p-2.5 text-mist-500 transition duration-200 hover:bg-mist-100 hover:text-ink-700 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-emerald-400/50 dark:text-mist-400 dark:hover:bg-ink-700 dark:hover:text-mist-100"
                        aria-label="إرفاق ملف"
                        data-testid="messenger-attach"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                    </button>

                    {{-- Categorised picker. Inserts at the caret rather than
                         appending, so an emoji can go mid-sentence; sending one
                         alone still renders oversized.

                         `align="start"` because this button sits at the inline
                         start of the composer row: the popover has to grow
                         AWAY from that edge. The previous `end-0` grew it off
                         the side of the viewport on a phone. --}}
                    <x-messenger.emoji-picker
                        align="start"
                        label="رموز تعبيرية"
                        testid="messenger-emoji"
                        x-on:emoji-picked="insertEmoji($event.detail)"
                    />

                    {{-- The caret listeners exist because the emoji picker no
                         longer takes focus: `insertEmoji` has to know where the
                         caret was when the user last touched the composer, and
                         `selectionStart` is unreliable to read from an element
                         that is not focused. --}}
                    <textarea
                        x-ref="composer"
                        x-model="draft"
                        @keydown.enter="onEnter($event)"
                        @keyup="rememberCaret()"
                        @click="rememberCaret()"
                        @select="rememberCaret()"
                        @input="rememberCaret()"
                        rows="1"
                        maxlength="5000"
                        placeholder="اكتب رسالتك... (Enter للإرسال، Shift+Enter لسطر جديد)"
                        class="max-h-32 min-h-[2.5rem] flex-1 resize-none rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                    ></textarea>
                    {{-- Icon only. The paper plane is universally understood in
                         a composer, and dropping the label keeps the control
                         row from wrapping on narrow screens. `rtl:-scale-x-100`
                         flips it to point the way text flows. --}}
                    <button
                        type="submit"
                        :disabled="draft.trim() === '' && pending.length === 0"
                        class="shrink-0 rounded-xl bg-emerald-400 p-2.5 text-emerald-950 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="إرسال"
                        title="إرسال"
                        data-testid="messenger-send"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                    </button>
                    </div>
                </form>
            @endif
        </section>

        {{--
            The modals live INSIDE the messenger scope.

            They used to sit after its closing tag, which was fine while they
            were native forms posting to a URL. Now that they submit over fetch
            they need `createGroup()` and `savePrivacy()`, and those are methods
            on this component — outside the scope the handlers would resolve to
            nothing and the buttons would silently do nothing.

            `fixed inset-0` takes them out of flow, so being nested in the
            two-pane flex row costs them nothing visually.
        --}}

        {{-- Forward modal. Self-contained — it carries its own x-data and its
             own fetch, and is opened by a window event from the per-message
             menu. Destinations are the caller's own inbox, the same
             visibleTo-scoped list the sidebar renders. --}}
        <x-messenger.forward-modal :conversations="$conversations" />

        {{-- Group creation modal --}}
        @if ($canCreateGroups)
            <div x-data="{ open: false }" @open-group-modal.window="open = true" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="open = false" class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm"></div>
                {{-- Submitted over fetch. A native POST here reloaded the whole
                     console to land on the new group. --}}
                <form @submit.prevent="createGroup($el)" class="relative w-full max-w-md rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800">
                    <h3 class="font-display text-lg font-bold text-ink-900 dark:text-ink-50">مجموعة جديدة</h3>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">أنت مسؤول المجموعة تلقائياً، ويمكن لأعضائها المشاركة دون إنشاء مجموعات جديدة.</p>

                    <label for="group_title" class="mt-4 block text-sm font-medium text-ink-700 dark:text-mist-200">اسم المجموعة</label>
                    <input id="group_title" name="title" type="text" required maxlength="120" class="mt-1.5 w-full rounded-xl border border-mist-200 bg-white p-2.5 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">

                    <p class="mt-4 text-sm font-medium text-ink-700 dark:text-mist-200">الأعضاء</p>
                    <div class="mt-2 max-h-48 space-y-1 overflow-y-auto rounded-xl border border-mist-200 p-2 dark:border-ink-600">
                        @forelse ($directory as $person)
                            <label class="flex items-center gap-2 rounded-lg px-2 py-1.5 hover:bg-mist-50 dark:hover:bg-ink-700">
                                <input type="checkbox" name="members[]" value="{{ $person['user_id'] }}" class="rounded border-mist-300 text-emerald-500 focus:ring-emerald-400">
                                <span class="text-sm text-ink-700 dark:text-mist-200">{{ $person['name'] }}</span>
                                <span class="text-xs text-mist-500 dark:text-mist-400">{{ $person['job_title'] ?? '' }}</span>
                            </label>
                        @empty
                            <p class="px-2 py-4 text-center text-xs text-mist-500 dark:text-mist-400">لا يوجد زملاء لديهم حسابات مفعّلة.</p>
                        @endforelse
                    </div>

                    <div class="mt-6 flex items-center justify-end gap-3">
                        <button type="button" @click="open = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                        <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-950 shadow-glow transition hover:bg-emerald-300 active:scale-95">إنشاء المجموعة</button>
                    </div>
                </form>
            </div>
        @endif

        {{-- Chat privacy settings --}}
        <div x-data="{ open: false }" @open-chat-privacy.window="open = true" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
            <div @click="open = false" class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm"></div>
            {{-- Saved over fetch and the indicators re-pulse straight after, so
                 turning read receipts off clears the ticks already on screen
                 without a reload. --}}
            <form @submit.prevent="savePrivacy($el).then((ok) => { if (ok) open = false; })" class="relative w-full max-w-md rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800">
                <h3 class="font-display text-lg font-bold text-ink-900 dark:text-ink-50">خصوصية المراسلات</h3>

                <label class="mt-4 flex items-start justify-between gap-4 rounded-xl border border-mist-200 px-4 py-3 dark:border-ink-600">
                    <span>
                        <span class="block text-sm font-medium text-ink-700 dark:text-mist-200">إخفاء «متصل الآن» و«آخر ظهور»</span>
                        <span class="mt-0.5 block text-xs text-mist-500 dark:text-mist-400">لن يرى زملاؤك حالتك أو وقت آخر ظهور لك.</span>
                    </span>
                    <input type="checkbox" name="chat_hide_last_seen" value="1" @checked(auth()->user()->chat_hide_last_seen) class="mt-1 rounded border-mist-300 text-emerald-500 focus:ring-emerald-400">
                </label>

                <label class="mt-3 flex items-start justify-between gap-4 rounded-xl border border-mist-200 px-4 py-3 dark:border-ink-600">
                    <span>
                        <span class="block text-sm font-medium text-ink-700 dark:text-mist-200">إخفاء مؤشرات القراءة</span>
                        {{-- Symmetric by design: hiding your read state also
                             hides everyone else's from you. Anything else lets
                             one side take without giving. --}}
                        <span class="mt-0.5 block text-xs text-mist-500 dark:text-mist-400">عند التفعيل لن يرى الآخرون أنك قرأت رسائلهم، ولن ترى أنت مؤشرات قراءتهم.</span>
                    </span>
                    <input type="checkbox" name="chat_hide_read_receipts" value="1" @checked(auth()->user()->chat_hide_read_receipts) class="mt-1 rounded border-mist-300 text-emerald-500 focus:ring-emerald-400">
                </label>

                <div class="mt-6 flex items-center justify-end gap-3">
                    <button type="button" @click="open = false" class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء</button>
                    <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-950 shadow-glow transition hover:bg-emerald-300 active:scale-95">حفظ</button>
                </div>
            </form>
        </div>
    </div>{{-- /veyraMessenger scope --}}

    @push('scripts')
        <script>
            function veyraMessenger(config) {
                return {
                    live: [],
                    draft: '',
                    /*
                     * The reply in progress: null, or
                     * { id, author, excerpt }. Held here rather than as a
                     * hidden input so the preview bar, the Esc handler and
                     * send() all read one source — a hidden field would let
                     * the bar say one thing and the request carry another.
                     */
                    reply: null,
                    /* Caret position in the composer — see rememberCaret(). */
                    caretStart: null,
                    caretEnd: null,
                    /* Files chosen but not yet sent: [{ key, file }]. Held as
                       real File objects so send() can hand them to FormData
                       untouched; `key` exists only to give x-for a stable
                       identity, since two files can share a name. */
                    pending: [],
                    /* Presence line under the thread title, and the id up to
                       which everyone else has read. Both seeded from the
                       server so the first paint is correct, then refreshed by
                       pulse(). Null read watermark means "show no ticks",
                       which is also what a privacy opt-out returns. */
                    peer: config.peerStatus,
                    readUpTo: config.readWatermark,
                    pulseTimer: null,
                    channelName: null,
                    config: config,

                    boot() {
                        this.scrollToEnd();

                        if (! this.config.conversationId) {
                            return;
                        }

                        /*
                         * Presence and read receipts refresh on a timer.
                         *
                         * 25s is a deliberate compromise: fast enough that a
                         * double tick appears while you are still looking at
                         * the message, slow enough that an idle thread costs
                         * ~2 requests a minute. It ALSO keeps the reader
                         * "online" — the pulse passes through
                         * `presence.touch`, so simply having the thread open
                         * counts as activity.
                         */
                        this.pulseTimer = window.setInterval(() => this.pulse(), 25000);

                        /*
                         * Thread switching is a wire:navigate now, which keeps
                         * the JS runtime alive across pages. Nothing tears
                         * this component down for us, so an un-cleared
                         * interval and an un-left channel would accumulate one
                         * of each per thread opened.
                         */
                        document.addEventListener('livewire:navigating', () => this.teardown(), { once: true });

                        if (! window.Echo) {
                            return;
                        }

                        /*
                         * Private, and named with the tenant segment as well as
                         * the conversation id — conversation ids are globally
                         * sequential, so without the tenant in the name they
                         * would be guessable across tenants.
                         */
                        this.channelName = `tenant.${this.config.tenantId}.conversations.${this.config.conversationId}`;

                        window.Echo.private(this.channelName).listen('.MessageSent', (payload) => {
                            // Own messages are already on screen from the
                            // optimistic append; re-adding them would double them.
                            if (payload.sender_id === this.config.userId) {
                                return;
                            }

                            if (this.live.some((m) => m.id === payload.id)) {
                                return;
                            }

                            /*
                             * The payload carries `parent_id` but not the
                             * parent's text — the broadcast is hand-built and
                             * deliberately ships nothing it does not have to.
                             * The quoted message is almost always already on
                             * screen, so the quote is reconstructed from the
                             * DOM rather than widening the payload.
                             */
                            payload.quote = this.quoteFor(payload.parent_id);

                            this.live.push(payload);
                            this.$nextTick(() => this.scrollToEnd());

                            // Reading it is what makes it read — refresh the
                            // watermark so the sender's tick turns over
                            // without waiting for the next timer beat.
                            this.pulse();
                        });
                    },

                    /*
                     * Alpine calls this when the component's tree is removed.
                     * Paired with the `livewire:navigating` listener in boot():
                     * whichever fires first wins, and running twice is safe.
                     */
                    destroy() {
                        this.teardown();
                    },

                    teardown() {
                        if (this.pulseTimer !== null) {
                            window.clearInterval(this.pulseTimer);
                            this.pulseTimer = null;
                        }

                        if (this.channelName && window.Echo) {
                            window.Echo.leave(this.channelName);
                            this.channelName = null;
                        }
                    },

                    /*
                     * One request, two answers: who is on the other end, and
                     * how far they have read. Both are already filtered by the
                     * privacy toggles server-side — the client never receives
                     * a value it is not allowed to display, so there is no
                     * second place for the rules to be got wrong.
                     */
                    async pulse() {
                        if (! this.config.conversationId) {
                            return;
                        }

                        try {
                            const response = await fetch(
                                this.config.pulseUrlTemplate.replace('__ID__', this.config.conversationId),
                                { headers: { 'Accept': 'application/json', 'X-Requested-With': 'XMLHttpRequest' } },
                            );

                            if (! response.ok) {
                                return;
                            }

                            const state = await response.json();
                            this.peer = state.peer;
                            this.readUpTo = state.read_up_to;
                        } catch (error) {
                            // A dropped beat is not worth surfacing; the next
                            // one in 25 seconds will correct the display.
                        }
                    },

                    /*
                     * Whether a message of mine has been read by everyone else.
                     * `readUpTo` is null whenever receipts are off on either
                     * side, so this answers false and only the single "sent"
                     * tick renders.
                     */
                    isRead(messageId) {
                        return this.readUpTo !== null && Number(messageId) <= Number(this.readUpTo);
                    },

                    /* ───────────────────────────────────────────────────────
                     * ASYNC FORMS
                     *
                     * Starting a thread, creating a group and saving privacy
                     * were all native form POSTs ending in a 302. Each one
                     * reloaded the document — losing scroll position, the
                     * composer draft and the websocket — to do something the
                     * page could have done in place.
                     * ─────────────────────────────────────────────────────── */

                    async startThread(userId) {
                        const created = await this.post(@js(route('tenant.messenger.store')), { user_id: userId });

                        if (! created) {
                            this.notify('error', 'تعذّر بدء المحادثة.');

                            return;
                        }

                        this.go(created.url);
                    },

                    async createGroup(form) {
                        const data = new FormData(form);
                        const members = data.getAll('members[]').map(Number);

                        if (members.length === 0) {
                            this.notify('error', 'اختر عضواً واحداً على الأقل.');

                            return;
                        }

                        const created = await this.post(@js(route('tenant.messenger.groups.store')), {
                            title: data.get('title'),
                            members: members,
                        });

                        if (! created) {
                            this.notify('error', 'تعذّر إنشاء المجموعة.');

                            return;
                        }

                        this.go(created.url);
                    },

                    async savePrivacy(form) {
                        const data = new FormData(form);

                        // A real PUT, not `_method` spoofing: Symfony only
                        // reads `_method` out of the POST parameter bag, which
                        // a JSON body never populates.
                        const saved = await this.post(
                            @js(route('tenant.messenger.privacy.update')),
                            {
                                chat_hide_last_seen: data.get('chat_hide_last_seen') ? 1 : 0,
                                chat_hide_read_receipts: data.get('chat_hide_read_receipts') ? 1 : 0,
                            },
                            'PUT',
                        );

                        if (! saved) {
                            this.notify('error', 'تعذّر حفظ الإعدادات.');

                            return false;
                        }

                        // Re-pulse immediately: switching receipts off has to
                        // clear the ticks already on screen, or the setting
                        // reads as ignored until the next beat.
                        await this.pulse();
                        this.notify('success', 'تم تحديث إعدادات خصوصية المراسلات.');

                        return true;
                    },

                    async send() {
                        const body = this.draft.trim();
                        const files = this.pending.map((entry) => entry.file);

                        // A file with no caption is a message; text with no
                        // file still is too. Only both empty is nothing.
                        if (body === '' && files.length === 0) {
                            return;
                        }

                        // Captured before clearing: the request is in flight
                        // while the bar is already gone, and the failure path
                        // below needs all three back.
                        const replyTo = this.reply;
                        const attached = this.pending;

                        this.draft = '';
                        this.reply = null;
                        this.pending = [];

                        const url = this.config.sendUrlTemplate.replace('__ID__', this.config.conversationId);

                        /*
                         * multipart/form-data, always — even with no files.
                         * One code path is worth more than the few bytes a
                         * JSON body would save, and a second path used only
                         * when files are present is a second path to get wrong.
                         *
                         * Content-Type is deliberately NOT set: the browser
                         * has to add the multipart boundary itself, and naming
                         * the type by hand omits it and produces an empty
                         * $request->all() server-side.
                         */
                        const form = new FormData();
                        form.append('body', body);

                        if (replyTo) {
                            form.append('parent_id', replyTo.id);
                        }

                        files.forEach((file) => form.append('attachments[]', file));

                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            },
                            body: form,
                        });

                        if (! response.ok) {
                            // Put the text back rather than losing what they
                            // typed — and the reply target and the files with
                            // it, or the retry would post as an unattached
                            // message with nothing on it.
                            this.draft = body;
                            this.reply = replyTo;
                            this.pending = attached;

                            this.notify('error', response.status === 422
                                ? 'تعذّر الإرسال: تحقّق من نوع الملف وحجمه.'
                                : 'تعذّر إرسال الرسالة.');

                            return;
                        }

                        const created = await response.json();

                        this.live.push({
                            id: created.id,
                            body: created.body,
                            sender_id: created.sender_id,
                            sender_name: this.config.userName,
                            sent_at: created.sent_at,
                            parent_id: replyTo?.id ?? null,
                            // Reused from the bar rather than re-read from the
                            // DOM: it is the same text the composer just
                            // promised to quote.
                            quote: replyTo ? { author: replyTo.author, excerpt: replyTo.excerpt } : null,
                            // Descriptors from the server, not from the local
                            // File objects: only the server knows the ids the
                            // preview and download URLs are built from.
                            attachments: created.attachments ?? [],
                        });

                        this.$nextTick(() => this.scrollToEnd());

                        // The new message starts unread by definition, so pull
                        // the watermark now rather than letting the timer show
                        // a stale tick for up to 25 seconds.
                        this.pulse();
                    },

                    /*
                     * Every action below patches the DOM from the endpoint's
                     * JSON and never reloads.
                     *
                     * The earlier version called location.reload() on success,
                     * which threw away scroll position, closed the thread on
                     * mobile, and lost anything half-typed in the composer —
                     * on a chat screen that reads as the app dropping what you
                     * were doing.
                     */
                    async post(url, payload = {}, method = 'POST') {
                        const response = await fetch(url, {
                            method: method,
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            },
                            body: JSON.stringify(payload),
                        });

                        return response.ok ? response.json() : null;
                    },

                    /* ───────────────────────────────────────────────────────
                     * REPLY
                     * ─────────────────────────────────────────────────────── */

                    /*
                     * The quote is lifted from the rendered bubble rather than
                     * fetched. The message is on screen — that is where the
                     * gesture started — so a request would spend a round trip
                     * re-reading text the browser is already displaying.
                     */
                    startReply(messageId) {
                        const row = document.querySelector(`[data-message="${messageId}"]`);

                        if (! row) {
                            return;
                        }

                        this.reply = {
                            id: messageId,
                            author: row.dataset.author || 'زميل',
                            excerpt: this.excerptOf(document.querySelector(`[data-body="${messageId}"]`)),
                        };

                        this.$nextTick(() => this.$refs.composer?.focus());
                    },

                    cancelReply() {
                        this.reply = null;
                    },

                    quoteFor(parentId) {
                        if (! parentId) {
                            return null;
                        }

                        const row = document.querySelector(`[data-message="${parentId}"]`);

                        if (! row) {
                            // Older than the loaded page. A quote naming an
                            // unknown message is worse than none.
                            return null;
                        }

                        return {
                            author: row.dataset.author || 'زميل',
                            excerpt: this.excerptOf(document.querySelector(`[data-body="${parentId}"]`)),
                        };
                    },

                    /*
                     * Collapses newlines so a multi-line message does not turn
                     * the one-line preview into a ragged strip, and caps the
                     * length so the bar cannot grow with the message.
                     */
                    excerptOf(element) {
                        const text = (element?.textContent ?? '').trim().replace(/\s+/g, ' ');

                        return text.length > 140 ? `${text.slice(0, 140)}…` : text;
                    },

                    jumpTo(messageId) {
                        const target = document.querySelector(`[data-message="${messageId}"]`);

                        if (! target) {
                            // Scrollback paging is not built yet, so this is a
                            // real outcome and needs to say so rather than
                            // looking like a dead control.
                            this.notify('info', 'الرسالة الأصلية أقدم من الجزء المعروض من المحادثة.');

                            return;
                        }

                        target.scrollIntoView({ behavior: 'smooth', block: 'center' });

                        // A flash rather than a persistent highlight: it
                        // answers "which one" and then gets out of the way.
                        const ring = ['ring-2', 'ring-emerald-400', 'rounded-2xl'];
                        target.classList.add(...ring);
                        window.setTimeout(() => target.classList.remove(...ring), 1400);
                    },

                    /* ───────────────────────────────────────────────────────
                     * MESSAGE MENU
                     * ─────────────────────────────────────────────────────── */

                    /*
                     * Whether a menu anchored here should open upward.
                     *
                     * Measured against the SCROLL PANE, not the viewport: the
                     * thread scrolls inside a fixed-height box, so a bubble can
                     * sit well down the page while being at the top of its own
                     * pane, where an upward menu would be clipped by
                     * `overflow-y-auto` and simply not appear.
                     *
                     * BOTH directions are measured. An earlier version asked
                     * only "is there room above?" and fell through to dropping
                     * down, which put the menu through the bottom of the pane
                     * for any message sitting just under the threshold.
                     */
                    placeMenu(anchor) {
                        const pane = this.$refs.scroll;

                        if (! pane || ! anchor) {
                            return true;
                        }

                        // Measured: five items at ~36px plus the wrapper's
                        // padding and border comes to 191px. Rounded up so a
                        // future sixth item does not silently start clipping.
                        const height = 200;

                        const anchorRect = anchor.getBoundingClientRect();
                        const paneRect = pane.getBoundingClientRect();

                        const roomBelow = paneRect.bottom - anchorRect.bottom;
                        const roomAbove = anchorRect.top - paneRect.top;

                        // Down is the default because it reads as "belonging
                        // to" the row it hangs from; up is the exception taken
                        // only when down does not fit.
                        if (roomBelow >= height) {
                            return false;
                        }

                        if (roomAbove >= height) {
                            return true;
                        }

                        // Neither side fits — a very short pane. Take whichever
                        // clips less rather than always picking the same one.
                        return roomAbove > roomBelow;
                    },

                    openForward(messageId) {
                        // Dispatched on window so the modal can live outside
                        // this component's element — see the note on the tag.
                        this.$dispatch('open-forward-modal', {
                            id: messageId,
                            excerpt: this.excerptOf(document.querySelector(`[data-body="${messageId}"]`)),
                        });
                    },

                    async copyMessage(messageId) {
                        const text = (document.querySelector(`[data-body="${messageId}"]`)?.textContent ?? '').trim();

                        if (text === '') {
                            return;
                        }

                        let copied = false;

                        /*
                         * navigator.clipboard does not exist outside a secure
                         * context, and this console is reached over plain http
                         * on internal networks. Without the execCommand
                         * fallback "نسخ النص" would silently do nothing for
                         * every user not on https — a dead menu item that
                         * looks like it worked.
                         */
                        if (navigator.clipboard?.writeText) {
                            try {
                                await navigator.clipboard.writeText(text);
                                copied = true;
                            } catch (error) {
                                copied = false;
                            }
                        }

                        if (! copied) {
                            copied = this.legacyCopy(text);
                        }

                        this.notify(
                            copied ? 'success' : 'error',
                            copied ? 'تم نسخ نص الرسالة.' : 'تعذّر نسخ النص من هذا المتصفح.',
                        );
                    },

                    legacyCopy(text) {
                        const area = document.createElement('textarea');
                        area.value = text;
                        area.setAttribute('readonly', '');
                        area.style.position = 'fixed';
                        area.style.opacity = '0';
                        document.body.appendChild(area);
                        area.select();

                        let ok = false;

                        try {
                            ok = document.execCommand('copy');
                        } catch (error) {
                            ok = false;
                        }

                        area.remove();

                        return ok;
                    },

                    async deleteMessage(messageId) {
                        if (! await this.confirmDelete()) {
                            return;
                        }

                        const response = await fetch(`{{ url('/app/messenger/messages') }}/${messageId}`, {
                            method: 'DELETE',
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            },
                        });

                        if (! response.ok) {
                            // 422 is the author-only rule; the menu item only
                            // renders on own messages, so reaching this means
                            // the page is stale rather than the user cheating.
                            this.notify('error', 'تعذّر حذف الرسالة.');

                            return;
                        }

                        /*
                         * Three places keep their own copy of a message and
                         * all three have to let go of it: the pinned bar, the
                         * reply in progress, and the live array.
                         */
                        const bar = document.querySelector('[data-pinned-bar]');

                        if (bar && String(bar.dataset.pinnedId) === String(messageId)) {
                            bar.classList.add('hidden');
                            bar.dataset.pinnedId = '';
                        }

                        if (this.reply && String(this.reply.id) === String(messageId)) {
                            this.cancelReply();
                        }

                        if (this.live.some((m) => String(m.id) === String(messageId))) {
                            // Alpine owns these nodes. Removing the element by
                            // hand as well would tear it out from under x-for.
                            this.live = this.live.filter((m) => String(m.id) !== String(messageId));
                        } else {
                            document.querySelector(`[data-message="${messageId}"]`)?.remove();
                        }

                        this.notify('success', 'تم حذف الرسالة.');
                    },

                    confirmDelete() {
                        if (! window.Swal) {
                            return Promise.resolve(window.confirm('حذف هذه الرسالة؟'));
                        }

                        return window.Swal.fire({
                            title: 'حذف الرسالة؟',
                            text: 'ستختفي من المحادثة لدى جميع المشاركين.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'نعم، احذف',
                            cancelButtonText: 'إلغاء',
                            confirmButtonColor: '#ef4444',
                            cancelButtonColor: '#64748b',
                            reverseButtons: true,
                        }).then((result) => result.isConfirmed);
                    },

                    /* ───────────────────────────────────────────────────────
                     * COMPOSER
                     * ─────────────────────────────────────────────────────── */

                    /* ───────────────────────────────────────────────────────
                     * ATTACHMENTS
                     * ─────────────────────────────────────────────────────── */

                    /*
                     * Queue chosen files, refusing the ones the server would
                     * refuse anyway.
                     *
                     * Checked here as well as server-side purely so the user
                     * finds out before waiting for a 10MB upload to come back
                     * 422 — the server rules in ConversationController::send
                     * remain the only enforcement.
                     */
                    addFiles(list) {
                        const maxFiles = {{ \App\Domain\Messaging\Support\MessageAttachmentStorage::MAX_FILES }};
                        const maxBytes = {{ \App\Domain\Messaging\Support\MessageAttachmentStorage::MAX_KILOBYTES }} * 1024;

                        for (const file of Array.from(list ?? [])) {
                            if (this.pending.length >= maxFiles) {
                                this.notify('error', `لا يمكن إرفاق أكثر من ${maxFiles} ملفات في الرسالة الواحدة.`);
                                break;
                            }

                            if (file.size > maxBytes) {
                                this.notify('error', `«${file.name}» أكبر من الحد المسموح (${this.humanSize(maxBytes)}).`);
                                continue;
                            }

                            // Name+size collide often enough (two screenshots
                            // from the same tool); the random suffix keeps
                            // x-for keys unique so removing one chip does not
                            // remove its twin.
                            this.pending.push({
                                key: `${file.name}:${file.size}:${Math.random().toString(36).slice(2)}`,
                                file: file,
                            });
                        }
                    },

                    /* Mirrors MessageAttachment::humanSize() so a pending chip
                       and the sent bubble read the same. */
                    humanSize(bytes) {
                        const size = Math.max(0, Number(bytes) || 0);

                        if (size >= 1048576) {
                            return `${Math.round((size / 1048576) * 10) / 10} م.ب`;
                        }

                        if (size >= 1024) {
                            return `${Math.round(size / 1024)} ك.ب`;
                        }

                        return `${size} بايت`;
                    },

                    /*
                     * Where the caret was the last time the composer was
                     * touched. Tracked in state rather than read off the
                     * element on demand, because the picker now stays open
                     * WITHOUT focus in the textarea, and an unfocused
                     * textarea's selection is not something to rely on.
                     */
                    rememberCaret() {
                        const el = this.$refs.composer;

                        if (! el) {
                            return;
                        }

                        this.caretStart = el.selectionStart;
                        this.caretEnd = el.selectionEnd;
                    },

                    /*
                     * Inserts at the caret, not at the end. Appending is only
                     * correct when the caret happens to be at the end, and
                     * people reach for an emoji mid-sentence as often as not.
                     *
                     * Deliberately does NOT focus the composer. Focus returning
                     * to the input is the gesture that DISMISSES the picker, so
                     * grabbing it here would close the picker after every
                     * single emoji — the behaviour this change removes.
                     */
                    insertEmoji(emoji) {
                        const el = this.$refs.composer;
                        const start = this.caretStart ?? this.draft.length;
                        const end = this.caretEnd ?? start;

                        this.draft = this.draft.slice(0, start) + emoji + this.draft.slice(end);

                        const caret = start + emoji.length;
                        this.caretStart = caret;
                        this.caretEnd = caret;

                        // Mirrored onto the element so the caret is in the
                        // right place whenever focus does come back.
                        this.$nextTick(() => el?.setSelectionRange(caret, caret));
                    },

                    /*
                     * Enter sends. Shift+Enter — and any other modifier —
                     * falls through to the textarea's own newline.
                     *
                     * Handled in a method rather than with `.enter.exact` so
                     * the IME guard below has somewhere to live: while an
                     * Arabic or CJK input method is composing, Enter commits
                     * the candidate word and must not also post the message.
                     * Browsers report that as `isComposing`, or as the legacy
                     * keyCode 229 on older WebKit.
                     */
                    onEnter(event) {
                        if (event.shiftKey || event.altKey || event.ctrlKey || event.metaKey) {
                            return;
                        }

                        if (event.isComposing || event.keyCode === 229) {
                            return;
                        }

                        event.preventDefault();
                        this.send();
                    },

                    /*
                     * Esc unwinds one layer at a time. Cancelling a reply and
                     * leaving the thread on the same keypress would throw away
                     * a decision the user had not finished making.
                     */
                    onEscape() {
                        if (this.reply) {
                            this.cancelReply();

                            return;
                        }

                        this.closeThread();
                    },

                    notify(icon, title) {
                        if (! window.Swal) {
                            return;
                        }

                        window.Swal.fire({
                            toast: true,
                            position: 'top-start',
                            icon: icon,
                            title: title,
                            showConfirmButton: false,
                            timer: 3000,
                            timerProgressBar: true,
                        });
                    },

                    /* Matches the server's `H:i` — 24-hour, Latin digits — so
                       a live bubble and a reloaded one show the same string. */
                    clock(iso) {
                        if (! iso) {
                            return '';
                        }

                        const at = new Date(iso);

                        if (Number.isNaN(at.getTime())) {
                            return '';
                        }

                        return `${String(at.getHours()).padStart(2, '0')}:${String(at.getMinutes()).padStart(2, '0')}`;
                    },

                    /*
                     * Reaction counts come back from the server rather than
                     * being incremented locally: the endpoint already computes
                     * the authoritative tally, and guessing it client-side
                     * drifts the moment two people react at once.
                     */
                    async react(messageId, emoji) {
                        const result = await this.post(
                            `{{ url('/app/messenger/messages') }}/${messageId}/react`,
                            { emoji: emoji },
                        );

                        if (! result) {
                            return;
                        }

                        this.renderReactions(messageId, result.counts);
                    },

                    renderReactions(messageId, counts) {
                        const host = document.querySelector(`[data-reactions="${messageId}"]`);

                        if (! host) {
                            return;
                        }

                        const entries = Object.entries(counts || {});
                        host.innerHTML = '';
                        host.classList.toggle('hidden', entries.length === 0);

                        entries.forEach(([emoji, total]) => {
                            const pill = document.createElement('span');
                            pill.className = 'flex items-center gap-1 rounded-full border border-ink-600/50 bg-ink-800/90 px-1.5 py-0.5 text-xs text-mist-200 shadow-sm backdrop-blur-sm';
                            pill.textContent = `${emoji} ${total}`;
                            host.appendChild(pill);
                        });
                    },

                    async pin(messageId) {
                        const result = await this.post(`{{ url('/app/messenger/messages') }}/${messageId}/pin`);

                        if (! result) {
                            return;
                        }

                        const bar = document.querySelector('[data-pinned-bar]');
                        const bodyEl = document.querySelector(`[data-body="${messageId}"]`);

                        if (! bar) {
                            return;
                        }

                        if (result.pinned) {
                            bar.querySelector('[data-pinned-text]').textContent = bodyEl?.textContent?.trim() ?? '';
                            bar.dataset.pinnedId = messageId;
                            bar.classList.remove('hidden');
                        } else {
                            bar.classList.add('hidden');
                            bar.dataset.pinnedId = '';
                        }
                    },

                    unpin() {
                        const bar = document.querySelector('[data-pinned-bar]');
                        const id = bar?.dataset?.pinnedId;

                        // Pin is a toggle, so unpinning is the same endpoint.
                        if (id) {
                            this.pin(id);
                        }
                    },

                    /*
                     * Both take the id explicitly now that the menu sits on the
                     * sidebar card: the card being acted on is very often NOT
                     * the thread currently open, so reading
                     * `config.conversationId` would have archived the wrong one.
                     */
                    async archive(conversationId) {
                        const done = await this.post(`{{ url('/app/messenger') }}/${conversationId}/archive`);

                        if (done) {
                            this.dropCard(conversationId);
                        }
                    },

                    async hideThread(conversationId) {
                        const done = await this.post(`{{ url('/app/messenger') }}/${conversationId}/hide`);

                        if (done) {
                            this.dropCard(conversationId);
                        }
                    },

                    /*
                     * Remove the card in place. Only when the shelved thread is
                     * the one currently OPEN does the pane need to change, and
                     * then it is a navigation rather than a silent blank.
                     */
                    dropCard(conversationId) {
                        if (String(conversationId) === String(this.config.conversationId)) {
                            this.go(this.config.indexUrl);

                            return;
                        }

                        document.querySelector(`[data-card="${conversationId}"]`)?.remove();
                    },

                    closeThread() {
                        if (! this.config.conversationId) {
                            return;
                        }

                        this.go(this.config.indexUrl);
                    },

                    /*
                     * SPA navigation, not a document load.
                     *
                     * `Livewire.navigate` swaps the body and keeps the JS
                     * runtime, so the sidebar, the theme class and the open
                     * socket survive; `location.href` threw all three away and
                     * repainted the whole console to move between two threads.
                     *
                     * Falls back to a hard navigation if livewire.js has not
                     * loaded, because a dead button is worse than a slow one.
                     */
                    go(url) {
                        this.teardown();

                        if (window.Livewire?.navigate) {
                            window.Livewire.navigate(url);

                            return;
                        }

                        window.location.href = url;
                    },

                    scrollToEnd() {
                        const pane = this.$refs.scroll;

                        if (pane) {
                            pane.scrollTop = pane.scrollHeight;
                        }
                    },
                };
            }
        </script>
    @endpush
</x-layouts.app>
