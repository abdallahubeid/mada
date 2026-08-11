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
            'sendUrlTemplate' => route('tenant.messenger.send', ['conversation' => '__ID__']),
        ]))"
        x-init="boot()"
        {{-- Esc closes the thread. Bound on window rather than the pane so it
             works wherever focus happens to be, and guarded on there being an
             open thread so it is inert on the list view. --}}
        @keydown.window.escape="closeThread()"
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
                                <form
                                    method="POST"
                                    action="{{ route('tenant.messenger.store') }}"
                                    x-show="q === '' || @js($person['name']).includes(q) || @js((string) ($person['department'] ?? '')).includes(q)"
                                >
                                    @csrf
                                    <input type="hidden" name="user_id" value="{{ $person['user_id'] }}">
                                    <button type="submit" class="flex w-full items-center gap-3 px-3 py-2.5 text-start transition hover:bg-mist-50 dark:hover:bg-ink-800">
                                        <span class="flex h-8 w-8 shrink-0 items-center justify-center rounded-full bg-emerald-400/15 text-xs font-bold text-emerald-800 dark:text-emerald-400">{{ $person['initial'] }}</span>
                                        <span class="min-w-0">
                                            <span class="block truncate text-sm font-medium text-ink-900 dark:text-ink-50">{{ $person['name'] }}</span>
                                            <span class="block truncate text-xs text-mist-500 dark:text-mist-400">{{ $person['job_title'] ?? $person['department'] ?? '—' }}</span>
                                        </span>
                                    </button>
                                </form>
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
                        <a href="{{ route('tenant.messenger.show', $thread['id']) }}" class="flex min-w-0 flex-1 items-center gap-3 px-4 py-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-mist-100 font-display text-sm font-bold text-mist-600 dark:bg-ink-700 dark:text-mist-300">{{ $thread['initial'] }}</span>
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
                    <a href="{{ route('tenant.messenger.index') }}" class="rounded-lg p-1.5 text-mist-500 transition hover:bg-mist-100 lg:hidden dark:hover:bg-ink-700" aria-label="رجوع">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:rotate-180" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M10.5 19.5 3 12m0 0 7.5-7.5M3 12h18" /></svg>
                    </a>
                    <span class="flex h-9 w-9 items-center justify-center rounded-full bg-emerald-400/15 font-display text-sm font-bold text-emerald-800 dark:text-emerald-400">{{ mb_substr($activeTitle, 0, 1) }}</span>
                    <div class="min-w-0 flex-1">
                        <p class="truncate font-display text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $activeTitle }}</p>
                        <p class="text-xs text-mist-500 dark:text-mist-400">محادثة خاصة</p>
                    </div>

                    {{-- The options menu lives on each SIDEBAR CARD, not here:
                         archiving or deleting from inside the open thread means
                         acting on the thread you are reading and then being
                         thrown out of it. On the card, the target of the action
                         is the thing you are pointing at. --}}

                    {{-- Close. Esc does the same, bound on the root below. --}}
                    <a href="{{ route('tenant.messenger.index') }}" class="shrink-0 rounded-lg p-2 text-mist-500 transition hover:bg-mist-100 hover:text-danger-solid dark:text-mist-400 dark:hover:bg-ink-700" aria-label="إغلاق المحادثة" title="إغلاق (Esc)" data-testid="messenger-close">
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
                        @endphp

                        @if ($message->isSystem())
                            {{-- The thread narrating itself: centred, no bubble,
                                 no reactions, no reply affordance. --}}
                            <p class="py-1 text-center text-[11px] text-mist-500 dark:text-mist-400">{{ $message->body }}</p>
                        @else
                            <div @class(['group flex items-end gap-1.5', 'flex-row-reverse' => $mine])>
                                {{-- relative + pb-1: the reaction pill hangs
                                     off the bubble's outer corner, so the
                                     bubble needs to be the positioning context
                                     and needs a little room beneath it. --}}
                                <div @class([
                                    'relative max-w-[75%] rounded-2xl px-3.5 pb-3 pt-2 text-sm leading-relaxed',
                                    'bg-emerald-400 text-emerald-950' => $mine,
                                    'bg-mist-100 text-ink-800 dark:bg-ink-700 dark:text-mist-100' => ! $mine,
                                ])>
                                    {{-- A lone emoji IS the message: rendered
                                         oversized and unboxed, per convention. --}}
                                    <p
                                        data-body="{{ $message->id }}"
                                        @class([
                                            'whitespace-pre-line break-words',
                                            'text-4xl leading-tight' => $message->isLoneEmoji(),
                                        ])
                                    >{{ $message->body }}</p>

                                    <p @class([
                                        'mt-1 text-[10px]',
                                        'text-emerald-900/70' => $mine,
                                        'text-mist-500 dark:text-mist-400' => ! $mine,
                                    ])>{{ $message->sent_at?->format('H:i') }}</p>

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

                                {{-- Hover actions. `opacity-0 group-hover` keeps
                                     them out of the way until wanted, and
                                     `focus-within` keeps them reachable by
                                     keyboard, which opacity alone would not. --}}
                                <div class="flex shrink-0 items-center gap-0.5 opacity-0 transition group-hover:opacity-100 focus-within:opacity-100">
                                    <div class="relative" x-data="{ open: false }">
                                        <button type="button" @click="open = ! open" class="rounded-full p-1.5 text-mist-500 transition hover:bg-mist-100 dark:hover:bg-ink-700" aria-label="تفاعل" title="تفاعل">
                                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
                                        </button>
                                        <div x-show="open" x-cloak @click.outside="open = false" class="absolute bottom-full z-20 mb-1 flex gap-0.5 rounded-full border border-mist-200 bg-white p-1 shadow-lg dark:border-ink-600 dark:bg-ink-900">
                                            @foreach ($reactionPalette as $emoji)
                                                <button type="button" @click="react({{ $message->id }}, @js($emoji)); open = false" class="rounded-full px-1.5 py-0.5 text-base transition hover:scale-125">{{ $emoji }}</button>
                                            @endforeach
                                        </div>
                                    </div>

                                    <button type="button" @click="pin({{ $message->id }})" class="rounded-full p-1.5 text-mist-500 transition hover:bg-mist-100 dark:hover:bg-ink-700" aria-label="تثبيت" title="تثبيت الرسالة">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M16 12V4h1V2H7v2h1v8l-2 2v2h5.2v6h1.6v-6H18v-2l-2-2Z" /></svg>
                                    </button>
                                </div>
                            </div>
                        @endif
                    @endforeach

                    {{-- Messages arriving over Reverb are appended here. --}}
                    <template x-for="m in live" :key="m.id">
                        <div class="flex" :class="m.sender_id === {{ auth()->id() }} ? 'justify-end' : ''">
                            <div
                                class="max-w-[75%] rounded-2xl px-3.5 py-2 text-sm leading-relaxed"
                                :class="m.sender_id === {{ auth()->id() }} ? 'bg-emerald-400 text-emerald-950' : 'bg-mist-100 text-ink-800 dark:bg-ink-700 dark:text-mist-100'"
                            >
                                <p class="whitespace-pre-line break-words" x-text="m.body"></p>
                            </div>
                        </div>
                    </template>
                </div>

                <form @submit.prevent="send()" class="flex items-end gap-2 border-t border-mist-100 p-3 dark:border-ink-700">
                    {{--
                        Attachments have NO backend yet: message_attachments
                        exists, but there is no upload endpoint, no private
                        disk wiring and no membership-checked download route.

                        So this control is visibly disabled rather than live.
                        A button that opens a file dialog and then silently
                        discards the file is worse than one that says it is not
                        ready — the same call made for the tenant suspend/cancel
                        modals elsewhere in this console.
                    --}}
                    <button
                        type="button"
                        disabled
                        title="إرفاق الملفات — قيد التطوير"
                        class="shrink-0 cursor-not-allowed rounded-xl p-2.5 text-mist-400 dark:text-mist-500"
                        aria-label="إرفاق ملف (غير متاح بعد)"
                        data-testid="messenger-attach"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m18.375 12.739-7.693 7.693a4.5 4.5 0 0 1-6.364-6.364l10.94-10.94A3 3 0 1 1 19.5 7.372L8.552 18.32m.009-.01-.01.01m5.699-9.941-7.81 7.81a1.5 1.5 0 0 0 2.112 2.13" /></svg>
                    </button>

                    {{-- Emoji picker. Inserts into the draft rather than
                         sending immediately, so an emoji can be combined with
                         text; sending one alone renders oversized. --}}
                    <div class="relative shrink-0" x-data="{ open: false }">
                        <button
                            type="button"
                            @click="open = ! open"
                            class="rounded-xl p-2.5 text-mist-500 transition hover:bg-mist-100 dark:text-mist-400 dark:hover:bg-ink-700"
                            aria-label="إدراج رمز تعبيري"
                            title="رموز تعبيرية"
                            data-testid="messenger-emoji"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.182 15.182a4.5 4.5 0 0 1-6.364 0M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0ZM9.75 9.75c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Zm5.625 0c0 .414-.168.75-.375.75s-.375-.336-.375-.75.168-.75.375-.75.375.336.375.75Zm-.375 0h.008v.015h-.008V9.75Z" /></svg>
                        </button>
                        {{-- z-40 clears the composer row and the hover action
                             menus (z-20/z-30); max-h + overflow keeps a long
                             palette from growing past the composer into the
                             thread. `bottom-full mb-3` floats it clear of the
                             button rather than sitting on top of it. --}}
                        <div
                            x-show="open"
                            x-cloak
                            x-transition.origin.bottom
                            @click.outside="open = false"
                            class="absolute bottom-full end-0 z-40 mb-3 grid max-h-56 w-64 grid-cols-8 gap-1 overflow-y-auto rounded-2xl border border-mist-200 bg-white p-2.5 shadow-2xl dark:border-ink-600 dark:bg-ink-900"
                        >
                            @foreach (['😀','😁','😂','🤣','😊','😍','😎','🤔','👍','👏','🙏','❤️','🔥','🎉','✅','⚠️','😅','😢','😮','🙌','💪','☕','📌','📎'] as $emoji)
                                <button type="button" @click="draft += @js($emoji); open = false; $refs.composer?.focus()" class="rounded p-1 text-lg transition hover:scale-125">{{ $emoji }}</button>
                            @endforeach
                        </div>
                    </div>

                    <textarea
                        x-ref="composer"
                        x-model="draft"
                        @keydown.enter.exact.prevent="send()"
                        rows="1"
                        maxlength="5000"
                        placeholder="اكتب رسالتك..."
                        class="max-h-32 min-h-[2.5rem] flex-1 resize-none rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                    ></textarea>
                    {{-- Icon only. The paper plane is universally understood in
                         a composer, and dropping the label keeps the control
                         row from wrapping on narrow screens. `rtl:-scale-x-100`
                         flips it to point the way text flows. --}}
                    <button
                        type="submit"
                        :disabled="draft.trim() === ''"
                        class="shrink-0 rounded-xl bg-emerald-400 p-2.5 text-emerald-950 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95 disabled:cursor-not-allowed disabled:opacity-40"
                        aria-label="إرسال"
                        title="إرسال"
                        data-testid="messenger-send"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" />
                        </svg>
                    </button>
                </form>
            @endif
        </section>
    </div>

        {{-- Group creation modal --}}
        @if ($canCreateGroups)
            <div x-data="{ open: false }" @open-group-modal.window="open = true" x-show="open" x-cloak class="fixed inset-0 z-50 flex items-center justify-center p-4">
                <div @click="open = false" class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm"></div>
                <form method="POST" action="{{ route('tenant.messenger.groups.store') }}" class="relative w-full max-w-md rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800">
                    @csrf
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
            <form method="POST" action="{{ route('tenant.messenger.privacy.update') }}" class="relative w-full max-w-md rounded-2xl border border-mist-200 bg-white p-6 shadow-xl dark:border-ink-600 dark:bg-ink-800">
                @csrf
                @method('PUT')
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

    @push('scripts')
        <script>
            function veyraMessenger(config) {
                return {
                    live: [],
                    draft: '',
                    config: config,

                    boot() {
                        this.scrollToEnd();

                        if (! this.config.conversationId || ! window.Echo) {
                            return;
                        }

                        /*
                         * Private, and named with the tenant segment as well as
                         * the conversation id — conversation ids are globally
                         * sequential, so without the tenant in the name they
                         * would be guessable across tenants.
                         */
                        window.Echo.private(
                            `tenant.${this.config.tenantId}.conversations.${this.config.conversationId}`
                        ).listen('.MessageSent', (payload) => {
                            // Own messages are already on screen from the
                            // optimistic append; re-adding them would double them.
                            if (payload.sender_id === this.config.userId) {
                                return;
                            }

                            if (this.live.some((m) => m.id === payload.id)) {
                                return;
                            }

                            this.live.push(payload);
                            this.$nextTick(() => this.scrollToEnd());
                        });
                    },

                    async send() {
                        const body = this.draft.trim();

                        if (body === '') {
                            return;
                        }

                        this.draft = '';

                        const url = this.config.sendUrlTemplate.replace('__ID__', this.config.conversationId);

                        const response = await fetch(url, {
                            method: 'POST',
                            headers: {
                                'Content-Type': 'application/json',
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                                'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                            },
                            body: JSON.stringify({ body: body }),
                        });

                        if (! response.ok) {
                            // Put the text back rather than losing what they typed.
                            this.draft = body;

                            return;
                        }

                        const created = await response.json();
                        this.live.push({ id: created.id, body: created.body, sender_id: created.sender_id });
                        this.$nextTick(() => this.scrollToEnd());
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
                    async post(url, payload = {}) {
                        const response = await fetch(url, {
                            method: 'POST',
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
                            window.location.href = @js(route('tenant.messenger.index'));

                            return;
                        }

                        document.querySelector(`[data-card="${conversationId}"]`)?.remove();
                    },

                    closeThread() {
                        if (! this.config.conversationId) {
                            return;
                        }

                        window.location.href = @js(route('tenant.messenger.index'));
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
