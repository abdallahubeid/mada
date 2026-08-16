@props([
    /** The caller's own thread list — the summarised array the sidebar renders. */
    'conversations' => [],
])

{{--
    Forward destination picker.

    Opened by dispatching on WINDOW:
        $dispatch('open-forward-modal', { id: 42, excerpt: '...' })

    ── SELF-CONTAINED ON PURPOSE ───────────────────────────────────────────
    It owns its own fetch rather than borrowing `post()` from the enclosing
    `madaMessenger` scope. A modal is rendered at the end of the page, outside
    the two-pane layout, so depending on an ancestor scope would make its
    placement in the document part of its contract — move the tag and it
    silently stops working.
    ────────────────────────────────────────────────────────────────────────

    ── THE LIST IS THE CALLER'S OWN INBOX ──────────────────────────────────
    Destinations come from the same `visibleTo`-scoped set the sidebar renders,
    so the picker cannot offer a thread the user is not in. That is a
    convenience, not the boundary: `ForwardMessageAction` re-checks membership
    on BOTH ends, and an id typed by hand into the request still 404s.
    ────────────────────────────────────────────────────────────────────────

    Single destination, not multi-select: the endpoint forwards to one thread
    per call, and a multi-select would have to fan out N requests and then
    explain a partial failure — "sent to 3 of 5" is a worse outcome than
    picking twice.
--}}
<div
    x-data="madaForwardModal(@js([
        'conversations' => $conversations,
        'urlTemplate' => route('tenant.messenger.forward', ['message' => '__ID__']),
    ]))"
    x-show="open"
    x-cloak
    @open-forward-modal.window="show($event.detail)"
    @keydown.escape.window="close()"
    class="fixed inset-0 z-50 flex items-center justify-center p-4"
    role="dialog"
    aria-modal="true"
    aria-labelledby="forward-modal-title"
    data-testid="messenger-forward-modal"
>
    <div @click="close()" class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm" aria-hidden="true"></div>

    <div class="relative flex max-h-[80vh] w-full max-w-md flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800">
        <div class="border-b border-mist-100 px-6 pb-4 pt-5 dark:border-ink-700">
            <h3 id="forward-modal-title" class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">إعادة توجيه الرسالة</h3>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اختر المحادثة التي تريد إرسال نسخة من الرسالة إليها.</p>

            {{-- What is actually being forwarded. Without it the picker asks
                 you to choose a destination for a message you can no longer
                 see, because the modal covers the thread. --}}
            <div x-show="excerpt" x-cloak class="mt-3 rounded-xl border-s-2 border-brand-500 bg-mist-50 px-3 py-2 dark:bg-ink-900">
                <p class="line-clamp-2 text-xs leading-relaxed text-ink-700 dark:text-mist-200" x-text="excerpt"></p>
            </div>
        </div>

        <div class="border-b border-mist-100 p-3 dark:border-ink-700">
            <label for="forward-search" class="sr-only">ابحث في محادثاتك</label>
            <input
                id="forward-search"
                x-ref="search"
                x-model="query"
                type="search"
                placeholder="ابحث في محادثاتك..."
                class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
            >
        </div>

        <div class="min-h-0 flex-1 overflow-y-auto p-2">
            <template x-for="thread in filtered" :key="thread.id">
                <button
                    type="button"
                    @click="submit(thread)"
                    :disabled="busy"
                    class="flex w-full items-center gap-3 rounded-xl px-3 py-2 text-start transition duration-150 hover:bg-mist-50 focus-visible:bg-mist-50 focus-visible:outline-none disabled:cursor-not-allowed disabled:opacity-50 dark:hover:bg-ink-700/50 dark:focus-visible:bg-ink-700/50"
                >
                    <span
                        class="flex h-9 w-9 shrink-0 items-center justify-center rounded-md font-display text-sm font-medium"
                        :class="thread.is_group
                            ? 'bg-brand-500/15 text-brand-700 dark:text-brand-300'
                            : 'bg-mist-100 text-mist-600 dark:bg-ink-700 dark:text-mist-300'"
                        x-text="thread.initial"
                    ></span>
                    <span class="min-w-0 flex-1">
                        <span class="block truncate text-sm font-semibold text-ink-900 dark:text-ink-50" x-text="thread.title"></span>
                        <span class="block truncate text-xs text-mist-500 dark:text-mist-400" x-text="thread.is_group ? 'مجموعة' : 'محادثة خاصة'"></span>
                    </span>

                    {{-- Spinner on the row being sent to, not a page-wide
                         overlay: the modal stays readable and it is obvious
                         which destination is in flight. --}}
                    <svg x-show="busy === thread.id" x-cloak class="h-4 w-4 shrink-0 animate-spin text-brand-600 dark:text-brand-300" viewBox="0 0 24 24" fill="none">
                        <circle class="opacity-25" cx="12" cy="12" r="10" stroke="currentColor" stroke-width="3"></circle>
                        <path class="opacity-75" fill="currentColor" d="M4 12a8 8 0 0 1 8-8v3a5 5 0 0 0-5 5H4Z"></path>
                    </svg>
                </button>
            </template>

            <p x-show="filtered.length === 0" x-cloak class="px-3 py-8 text-center text-sm text-mist-500 dark:text-mist-400">
                لا توجد محادثة مطابقة.
            </p>
        </div>

        <div class="flex items-center justify-end gap-3 border-t border-mist-100 px-6 py-4 dark:border-ink-700">
            <button
                type="button"
                @click="close()"
                class="rounded-xl px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:bg-mist-100 focus-visible:outline-none focus-visible:ring-2 focus-visible:ring-brand-500/50 dark:text-mist-300 dark:hover:bg-ink-700"
            >
                إلغاء
            </button>
        </div>
    </div>
</div>

@once
    @push('scripts')
        <script>
            function madaForwardModal(config) {
                return {
                    open: false,
                    /* The message being forwarded, and a copy of its text for
                       the preview block. */
                    messageId: null,
                    excerpt: '',
                    query: '',
                    /* Holds the destination id while its request is in flight,
                       so the spinner can sit on the row that was clicked and a
                       double-tap cannot send twice. */
                    busy: null,
                    conversations: config.conversations ?? [],

                    get filtered() {
                        const q = this.query.trim();

                        if (q === '') {
                            return this.conversations;
                        }

                        return this.conversations.filter((t) => String(t.title ?? '').includes(q));
                    },

                    show(detail) {
                        this.messageId = detail?.id ?? null;
                        this.excerpt = detail?.excerpt ?? '';
                        this.query = '';
                        this.busy = null;
                        this.open = true;

                        // Focus lands in the filter rather than on the first
                        // destination: a stray Enter on a focused row would
                        // forward to whoever happens to be top of the list.
                        this.$nextTick(() => this.$refs.search?.focus());
                    },

                    close() {
                        if (this.busy !== null) {
                            return;
                        }

                        this.open = false;
                        this.messageId = null;
                        this.excerpt = '';
                    },

                    async submit(thread) {
                        if (this.messageId === null || this.busy !== null) {
                            return;
                        }

                        this.busy = thread.id;

                        try {
                            const response = await fetch(
                                config.urlTemplate.replace('__ID__', this.messageId),
                                {
                                    method: 'POST',
                                    headers: {
                                        'Content-Type': 'application/json',
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') ?? '',
                                    },
                                    body: JSON.stringify({ conversation_id: thread.id }),
                                },
                            );

                            this.busy = null;

                            if (! response.ok) {
                                this.notify('error', 'تعذّرت إعادة التوجيه. حاول مرة أخرى.');

                                return;
                            }

                            this.open = false;
                            this.messageId = null;
                            this.excerpt = '';
                            this.notify('success', `تمت إعادة التوجيه إلى ${thread.title}`);
                        } catch (error) {
                            // Offline, or the request was cut. Leaving `busy`
                            // set would lock the modal open with no way out.
                            this.busy = null;
                            this.notify('error', 'تعذّر الاتصال بالخادم.');
                        }
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
                };
            }
        </script>
    @endpush
@endonce
