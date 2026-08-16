@php
    $user = auth()->user();
@endphp

<x-layouts.app title="رسائل التواصل">
    <div
        x-data="madaTenantContactInbox({
            search: @js($search),
            threads: @js($threads),
            counts: @js($counts),
            canManage: @js($canManage),
            threadsUrl: @js($threadsUrl),
            tenantId: @js($user?->tenant_id),
            userId: @js($user?->id),
            csrf: @js(csrf_token()),
        })"
        @keydown.escape.window="closeChat()"
        class="space-y-0"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">رسائل التواصل</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    رسائل نموذج الموقع العام — محادثة واحدة لكل بريد إلكتروني، مع تحديث مباشر عبر Reverb.
                </p>
            </div>
        </div>

        <div class="mt-4 flex flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm lg:h-[calc(100dvh-12rem)] lg:flex-row dark:border-ink-600 dark:bg-ink-800">
            <div class="flex max-h-[42vh] shrink-0 flex-col border-b border-mist-200 lg:max-h-none lg:w-80 lg:border-b-0 lg:border-e dark:border-ink-700">
                <div class="space-y-3 border-b border-mist-100 p-3 dark:border-ink-700">
                    <div class="grid grid-cols-2 gap-1 rounded-xl bg-mist-100 p-1 dark:bg-ink-900">
                        <button
                            type="button"
                            @click="setFolder('active')"
                            :class="folder === 'active'
                                ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-800 dark:text-ink-50'
                                : 'text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200'"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition"
                        >
                            الرسائل النشطة
                            <span
                                class="rounded-md px-1.5 py-0.5 text-xs font-bold"
                                :class="folder === 'active' ? 'bg-brand-500/15 text-brand-600 dark:text-brand-300' : 'bg-mist-200/80 text-mist-500 dark:bg-ink-700 dark:text-mist-400'"
                                x-text="counts.active ?? 0"
                            >{{ $counts['active'] ?? 0 }}</span>
                        </button>
                        <button
                            type="button"
                            @click="setFolder('archived')"
                            :class="folder === 'archived'
                                ? 'bg-white text-ink-900 shadow-sm dark:bg-ink-800 dark:text-ink-50'
                                : 'text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200'"
                            class="inline-flex items-center justify-center gap-1.5 rounded-lg px-2 py-2 text-xs font-semibold transition"
                        >
                            الأرشيف
                            <span
                                class="rounded-md px-1.5 py-0.5 text-xs font-bold"
                                :class="folder === 'archived' ? 'bg-brand-500/15 text-brand-600 dark:text-brand-300' : 'bg-mist-200/80 text-mist-500 dark:bg-ink-700 dark:text-mist-400'"
                                x-text="counts.archived ?? 0"
                            >{{ $counts['archived'] ?? 0 }}</span>
                        </button>
                    </div>

                    <form method="GET" action="{{ route('tenant.contact-messages.index') }}" class="relative" @submit.prevent="searchThreads()">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <input
                            type="search"
                            name="q"
                            x-model="search"
                            placeholder="ابحث بالاسم أو البريد أو الموضوع..."
                            class="w-full rounded-xl border border-mist-200 bg-white py-2 ps-9 pe-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                        >
                    </form>
                </div>

                <div class="flex-1 overflow-y-auto" data-thread-list x-ref="threadList">
                    @include('tenant.contact-messages._thread-list', [
                        'threads' => $threads,
                        'canManage' => $canManage,
                        'folder' => 'active',
                    ])
                </div>
            </div>

            <div class="flex min-w-0 flex-1 flex-col" data-chat-panel x-ref="chatPanel">
                <div data-chat-placeholder class="flex flex-1 flex-col items-center justify-center p-10 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-ink-700 dark:text-mist-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                    </span>
                    <p class="mt-3 text-sm font-medium text-ink-900 dark:text-ink-50">اختر محادثة لبدء القراءة</p>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اضغط على محادثة من القائمة لعرضها فورًا دون إعادة تحميل الصفحة.</p>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script>
            document.addEventListener('alpine:init', () => {
                Alpine.data('madaTenantContactInbox', (config) => ({
                    search: config.search || '',
                    threads: config.threads || [],
                    counts: config.counts || { active: 0, archived: 0 },
                    canManage: Boolean(config.canManage),
                    threadsUrl: config.threadsUrl || '',
                    tenantId: config.tenantId,
                    userId: config.userId,
                    csrf: config.csrf,
                    folder: 'active',
                    selectedThreadId: null,
                    replyUrl: null,
                    loadingThread: false,
                    loadingThreads: false,
                    sendingReply: false,
                    openMenuId: null,
                    echoChannel: null,

                    init() {
                        this.listenLive();
                    },

                    destroy() {
                        this.echoChannel?.stopListening?.('.NewContactMessageReceived');
                    },

                    toggleMenu(threadId) {
                        this.openMenuId = this.openMenuId === threadId ? null : threadId;
                    },

                    csrfToken() {
                        return this.csrf || document.querySelector('meta[name="csrf-token"]')?.getAttribute('content') || '';
                    },

                    async setFolder(folder) {
                        if (this.folder === folder || this.loadingThreads) {
                            return;
                        }

                        this.folder = folder;
                        this.openMenuId = null;
                        this.closeChat();
                        await this.fetchThreads();
                    },

                    async searchThreads() {
                        await this.fetchThreads();
                    },

                    async fetchThreads() {
                        if (! this.threadsUrl || this.loadingThreads) {
                            return;
                        }

                        this.loadingThreads = true;

                        try {
                            const url = new URL(this.threadsUrl, window.location.origin);
                            url.searchParams.set('folder', this.folder);
                            if (this.search) {
                                url.searchParams.set('q', this.search);
                            }
                            if (this.selectedThreadId) {
                                url.searchParams.set('thread', String(this.selectedThreadId));
                            }

                            const response = await fetch(url.toString(), {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                cache: 'no-store',
                            });

                            if (! response.ok) {
                                throw new Error('threads_failed');
                            }

                            const payload = await response.json();
                            this.threads = Array.isArray(payload.threads) ? payload.threads : [];
                            this.counts = payload.counts || this.counts;
                            if (payload.folder) {
                                this.folder = payload.folder;
                            }
                            this.renderThreadList(this.threads);
                        } catch (error) {
                            this.toastError('تعذر تحميل قائمة المحادثات. حاول مرة أخرى.');
                        } finally {
                            this.loadingThreads = false;
                        }
                    },

                    renderThreadList(threads) {
                        const list = this.$refs.threadList;
                        if (! list) {
                            return;
                        }

                        if (! threads.length) {
                            this.ensureEmptyState(true);
                            return;
                        }

                        list.innerHTML = '';
                        threads.forEach((thread) => {
                            list.appendChild(this.buildThreadRow(thread));
                        });
                    },

                    buildThreadRow(thread) {
                        const isArchived = Boolean(thread.is_archived) || this.folder === 'archived';
                        const row = document.createElement('div');
                        row.dataset.threadId = String(thread.id);
                        row.dataset.showUrl = thread.show_url || '';
                        row.dataset.archiveUrl = thread.archive_url || '';
                        row.dataset.unarchiveUrl = thread.unarchive_url || '';
                        row.dataset.destroyUrl = thread.destroy_url || '';
                        row.dataset.isArchived = isArchived ? '1' : '0';
                        row.className = 'group relative flex gap-3 border-b border-mist-100 p-4 transition duration-150 dark:border-ink-700 hover:bg-mist-50 dark:hover:bg-ink-700/40';

                        if (thread.is_selected || Number(this.selectedThreadId) === Number(thread.id)) {
                            row.classList.add('bg-brand-500/[0.06]', 'border-s-2', 'border-s-brand-500');
                            row.classList.remove('hover:bg-mist-50', 'dark:hover:bg-ink-700/40');
                        }

                        const openBtn = document.createElement('button');
                        openBtn.type = 'button';
                        openBtn.className = 'flex min-w-0 flex-1 gap-3 text-start';
                        openBtn.addEventListener('click', () => this.selectThread(thread.id, thread.show_url));

                        const unreadHtml = thread.unread
                            ? `<span data-thread-unread class="inline-flex min-w-5 items-center justify-center rounded-full bg-brand-500 px-1.5 text-xs font-bold text-white">${Math.max(1, Number(thread.unread_count || 1))}</span>`
                            : '';

                        openBtn.innerHTML = `
                            <img src="${this.escapeAttr(thread.avatar_url || '')}" alt="" class="h-10 w-10 shrink-0 rounded-full border border-slate-700 object-cover">
                            <div class="min-w-0 flex-1">
                                <div class="flex items-center justify-between gap-2 pe-8">
                                    <p class="truncate text-sm font-semibold text-ink-900 dark:text-ink-50">${this.escapeHtml(thread.display_name || '')}</p>
                                    <span class="shrink-0 text-xs text-mist-400 dark:text-mist-500">${thread.last_message_at ? 'الآن' : ''}</span>
                                </div>
                                <p class="mt-0.5 truncate text-sm font-medium text-ink-700 dark:text-mist-200">${this.escapeHtml(thread.subject || '')}</p>
                                <p class="mt-0.5 truncate text-xs text-mist-500 dark:text-mist-400" data-thread-snippet>${this.escapeHtml(thread.snippet || '')}</p>
                                <div class="mt-1.5 flex items-center gap-2">${unreadHtml}</div>
                            </div>
                        `;
                        row.appendChild(openBtn);

                        if (this.canManage) {
                            row.appendChild(this.buildThreadMenu(
                                thread.id,
                                thread.archive_url,
                                thread.unarchive_url,
                                thread.destroy_url,
                                isArchived,
                            ));
                        }

                        return row;
                    },

                    async archiveThread(threadId, archiveUrl) {
                        if (! archiveUrl) {
                            return;
                        }

                        try {
                            const response = await fetch(archiveUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                },
                            });

                            if (! response.ok) {
                                throw new Error('archive_failed');
                            }

                            const payload = await response.json();
                            this.removeThreadFromList(threadId);
                            this.bumpCounts({ active: -1, archived: 1 });
                            if (Number(this.selectedThreadId) === Number(threadId)) {
                                this.closeChat();
                            }
                            this.toastSuccess(payload.message || 'تم نقل المحادثة إلى الأرشيف');
                        } catch (error) {
                            this.toastError('تعذر أرشفة المحادثة. حاول مرة أخرى.');
                        }
                    },

                    async unarchiveThread(threadId, unarchiveUrl) {
                        if (! unarchiveUrl) {
                            return;
                        }

                        try {
                            const response = await fetch(unarchiveUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                },
                            });

                            if (! response.ok) {
                                throw new Error('unarchive_failed');
                            }

                            const payload = await response.json();
                            this.removeThreadFromList(threadId);
                            this.bumpCounts({ active: 1, archived: -1 });
                            if (Number(this.selectedThreadId) === Number(threadId)) {
                                this.closeChat();
                            }
                            this.toastSuccess(payload.message || 'تم إعادة المحادثة إلى الرسائل النشطة');
                        } catch (error) {
                            this.toastError('تعذر إلغاء أرشفة المحادثة. حاول مرة أخرى.');
                        }
                    },

                    bumpCounts(delta) {
                        this.counts = {
                            active: Math.max(0, Number(this.counts.active || 0) + Number(delta.active || 0)),
                            archived: Math.max(0, Number(this.counts.archived || 0) + Number(delta.archived || 0)),
                        };
                    },

                    deleteThread(threadId, destroyUrl) {
                        if (! destroyUrl || typeof Swal === 'undefined') {
                            return;
                        }

                        Swal.fire({
                            title: 'هل أنت تأكد من الحذف؟',
                            text: 'سيتم الحذف الناعم ويمكن الاستعادة من سلة المحذوفات.',
                            icon: 'warning',
                            showCancelButton: true,
                            confirmButtonText: 'نعم، احذف',
                            cancelButtonText: 'إلغاء',
                            confirmButtonColor: '#b42318',
                            cancelButtonColor: '#5a5262',
                            reverseButtons: true,
                        }).then(async (result) => {
                            if (! result.isConfirmed) {
                                return;
                            }

                            try {
                                const response = await fetch(destroyUrl, {
                                    method: 'DELETE',
                                    headers: {
                                        'Accept': 'application/json',
                                        'X-Requested-With': 'XMLHttpRequest',
                                        'X-CSRF-TOKEN': this.csrfToken(),
                                    },
                                });

                                if (! response.ok) {
                                    throw new Error('delete_failed');
                                }

                                const payload = await response.json();
                                this.removeThreadFromList(threadId);
                                if (this.folder === 'archived') {
                                    this.bumpCounts({ archived: -1 });
                                } else {
                                    this.bumpCounts({ active: -1 });
                                }
                                if (Number(this.selectedThreadId) === Number(threadId)) {
                                    this.closeChat();
                                }
                                this.toastSuccess(payload.message || 'تم حذف المحادثة بنجاح', {
                                    undoUrl: payload.undo_url,
                                    undoLabel: payload.undo_label || 'تراجع',
                                    undoMethod: payload.undo_method || 'POST',
                                });
                            } catch (error) {
                                this.toastError('تعذر حذف المحادثة. حاول مرة أخرى.');
                            }
                        });
                    },

                    removeThreadFromList(threadId) {
                        const row = this.$refs.threadList?.querySelector(`[data-thread-id="${threadId}"]`);
                        if (row) {
                            row.classList.add('opacity-0', 'transition', 'duration-200');
                            setTimeout(() => {
                                row.remove();
                                this.ensureEmptyState();
                            }, 200);
                        }

                        this.threads = this.threads.filter((item) => Number(item.id) !== Number(threadId));
                    },

                    ensureEmptyState(force = false) {
                        const list = this.$refs.threadList;
                        if (! list) {
                            return;
                        }

                        if (! force && list.querySelector('[data-thread-id]')) {
                            return;
                        }

                        const emptyHint = this.folder === 'archived'
                            ? 'لا توجد محادثات في الأرشيف.'
                            : 'ستظهر رسائل نموذج التواصل هنا تلقائيًا.';

                        list.innerHTML = `
                            <div class="p-8 text-center" data-thread-empty>
                                <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد محادثات</p>
                                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">${emptyHint}</p>
                            </div>
                        `;
                    },

                    async selectThread(threadId, showUrl) {
                        if (! showUrl || this.loadingThread) {
                            return;
                        }

                        this.loadingThread = true;

                        try {
                            const response = await fetch(showUrl, {
                                headers: {
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                                cache: 'no-store',
                            });

                            if (! response.ok) {
                                throw new Error('load_failed');
                            }

                            const payload = await response.json();
                            this.renderChat(payload);
                            this.selectedThreadId = Number(threadId);
                            this.replyUrl = payload.thread?.reply_url || null;
                            this.highlightThread(threadId);
                            this.clearUnreadBadge(threadId);
                        } catch (error) {
                            this.toastError('تعذر فتح المحادثة. حاول مرة أخرى.');
                        } finally {
                            this.loadingThread = false;
                        }
                    },

                    closeChat() {
                        this.selectedThreadId = null;
                        this.replyUrl = null;
                        this.clearThreadHighlight();
                        this.renderPlaceholder();
                    },

                    renderPlaceholder() {
                        const panel = this.$refs.chatPanel;
                        if (! panel) {
                            return;
                        }

                        panel.innerHTML = `
                            <div data-chat-placeholder class="flex flex-1 flex-col items-center justify-center p-10 text-center">
                                <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-ink-700 dark:text-mist-500">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                                </span>
                                <p class="mt-3 text-sm font-medium text-ink-900 dark:text-ink-50">اختر محادثة لبدء القراءة</p>
                                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اضغط على محادثة من القائمة لعرضها فورًا دون إعادة تحميل الصفحة.</p>
                            </div>
                        `;
                    },

                    renderChat(payload) {
                        const panel = this.$refs.chatPanel;
                        const thread = payload.thread || {};
                        const messages = Array.isArray(payload.messages) ? payload.messages : [];
                        const canReply = payload.can_reply ?? (this.canManage && this.folder !== 'archived');

                        if (! panel) {
                            return;
                        }

                        const messageHtml = messages.map((message) => this.messageBubbleHtml(message)).join('');
                        const replyHtml = canReply ? `
                            <div class="border-t border-mist-100 p-3 dark:border-ink-700">
                                <form data-reply-form class="flex items-end gap-2">
                                    <textarea
                                        name="body"
                                        rows="1"
                                        required
                                        placeholder="اكتب ردًا يُرسل بالبريد ويُحفظ في المحادثة..."
                                        class="max-h-32 min-h-[2.75rem] flex-1 resize-none rounded-xl border border-mist-200 bg-white px-3 py-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                                    ></textarea>
                                    <button type="submit" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white shadow-glow transition duration-200 hover:bg-brand-600 active:scale-95" aria-label="إرسال">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                    </button>
                                </form>
                                <p class="mt-1.5 px-1 text-xs text-mist-400 dark:text-mist-500">Esc أو × لإغلاق العرض دون إعادة تحميل الصفحة.</p>
                            </div>
                        ` : (this.canManage ? `
                            <div class="border-t border-mist-100 p-3 dark:border-ink-700">
                                <p class="rounded-xl bg-mist-50 px-3 py-2 text-xs text-mist-500 dark:bg-ink-900 dark:text-mist-400">هذه المحادثة في الأرشيف — ألغِ الأرشفة للرد عليها.</p>
                            </div>
                        ` : '');

                        panel.innerHTML = `
                            <div data-chat-active class="flex h-full min-h-[20rem] flex-col">
                                <div class="flex items-center justify-between gap-3 border-b border-mist-100 p-4 dark:border-ink-700">
                                    <div class="flex min-w-0 items-center gap-3">
                                        <img src="${this.escapeAttr(thread.avatar_url || '')}" alt="" class="h-10 w-10 shrink-0 rounded-full border border-slate-700 object-cover">
                                        <div class="min-w-0">
                                            <p class="truncate font-display text-base font-medium text-ink-900 dark:text-ink-50">${this.escapeHtml(thread.subject || '')}</p>
                                            <p class="truncate text-xs text-mist-500 dark:text-mist-400">
                                                ${this.escapeHtml(thread.display_name || '')} · <span dir="ltr">${this.escapeHtml(thread.email || '')}</span>
                                            </p>
                                        </div>
                                    </div>
                                    <button
                                        type="button"
                                        data-close-chat
                                        class="inline-flex items-center gap-1.5 rounded-xl border border-mist-200 px-3 py-2 text-sm font-medium text-mist-500 transition hover:border-danger-solid/40 hover:bg-danger-solid/10 hover:text-danger-solid dark:border-ink-600"
                                        title="إغلاق المحادثة"
                                        aria-label="إغلاق المحادثة"
                                    >
                                        <span>إغلاق المحادثة</span>
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                    </button>
                                </div>
                                <div data-message-list class="flex-1 space-y-4 overflow-y-auto bg-neutral-50 p-4 sm:p-6 dark:bg-ink-900/50">
                                    ${messageHtml}
                                </div>
                                ${replyHtml}
                            </div>
                        `;

                        panel.querySelector('[data-close-chat]')?.addEventListener('click', () => this.closeChat());
                        panel.querySelector('[data-reply-form]')?.addEventListener('submit', (event) => this.submitReply(event));
                        this.$nextTick(() => this.scrollMessages());
                    },

                    async submitReply(event) {
                        event.preventDefault();

                        if (! this.replyUrl || this.sendingReply) {
                            return;
                        }

                        const form = event.target;
                        const textarea = form.querySelector('textarea[name="body"]');
                        const body = (textarea?.value || '').trim();

                        if (! body) {
                            return;
                        }

                        this.sendingReply = true;

                        try {
                            const response = await fetch(this.replyUrl, {
                                method: 'POST',
                                headers: {
                                    'Accept': 'application/json',
                                    'Content-Type': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                    'X-CSRF-TOKEN': this.csrfToken(),
                                },
                                body: JSON.stringify({ body }),
                            });

                            if (! response.ok) {
                                throw new Error('reply_failed');
                            }

                            const payload = await response.json();
                            if (payload.chat_message) {
                                this.appendMessageBubble(payload.chat_message);
                            }
                            if (payload.thread?.snippet) {
                                this.updateThreadSnippet(this.selectedThreadId, payload.thread.snippet);
                            }
                            if (textarea) {
                                textarea.value = '';
                            }
                            this.toastSuccess(payload.message || 'تم إرسال الرد بنجاح.');
                        } catch (error) {
                            this.toastError('تعذر إرسال الرد. حاول مرة أخرى.');
                        } finally {
                            this.sendingReply = false;
                        }
                    },

                    messageBubbleHtml(message) {
                        const isStaff = Boolean(message.is_staff);
                        const receipt = this.receiptHtml(message.receipt || 'pending', isStaff);

                        return `
                            <div class="flex items-end gap-2 ${isStaff ? 'flex-row-reverse' : 'flex-row'}" data-message-id="${message.id}">
                                <img src="${this.escapeAttr(message.avatar_url || '')}" alt="" class="h-8 w-8 shrink-0 rounded-full border border-slate-700 object-cover">
                                <div class="max-w-[80%]">
                                    <div class="rounded-2xl px-4 py-3 text-sm shadow-sm ${isStaff
                                        ? 'bg-brand-500 text-white rounded-se-none'
                                        : 'border border-mist-200 bg-white text-white rounded-ss-none dark:border-ink-700 dark:bg-ink-800 dark:text-mist-100'}">
                                        ${this.escapeHtml(message.body || '')}
                                    </div>
                                    <div class="mt-1 flex items-center gap-1.5 px-1 text-xs text-mist-400 dark:text-mist-500 ${isStaff ? 'justify-end' : 'justify-start'}">
                                        <span>${this.escapeHtml(message.sender_name || '')} · الآن</span>
                                        ${receipt}
                                    </div>
                                </div>
                            </div>
                        `;
                    },

                    receiptHtml(status, onDark) {
                        const color = status === 'read'
                            ? 'text-sky-500'
                            : (onDark ? 'text-white/70' : 'text-mist-400');

                        if (status === 'pending') {
                            return `<span class="inline-flex ${color}" title="تم الحفظ">✓</span>`;
                        }

                        return `<span class="inline-flex ${color}" title="${status === 'read' ? 'تمت القراءة' : 'تم التسليم'}">✓✓</span>`;
                    },

                    highlightThread(threadId) {
                        this.clearThreadHighlight();
                        const row = this.$refs.threadList?.querySelector(`[data-thread-id="${threadId}"]`);
                        if (! row) {
                            return;
                        }

                        row.classList.add('bg-brand-500/[0.06]', 'border-s-2', 'border-s-brand-500');
                        row.classList.remove('hover:bg-mist-50', 'dark:hover:bg-ink-700/40');
                    },

                    clearThreadHighlight() {
                        this.$refs.threadList?.querySelectorAll('[data-thread-id]').forEach((row) => {
                            row.classList.remove('bg-brand-500/[0.06]', 'border-s-2', 'border-s-brand-500');
                            row.classList.add('hover:bg-mist-50', 'dark:hover:bg-ink-700/40');
                        });
                    },

                    clearUnreadBadge(threadId) {
                        this.$refs.threadList
                            ?.querySelector(`[data-thread-id="${threadId}"]`)
                            ?.querySelector('[data-thread-unread]')
                            ?.remove();
                    },

                    updateThreadSnippet(threadId, snippet) {
                        const node = this.$refs.threadList
                            ?.querySelector(`[data-thread-id="${threadId}"]`)
                            ?.querySelector('[data-thread-snippet]');
                        if (node) {
                            node.textContent = snippet;
                        }
                    },

                    scrollMessages() {
                        const list = this.$refs.chatPanel?.querySelector('[data-message-list]');
                        if (list) {
                            list.scrollTop = list.scrollHeight;
                        }
                    },

                    listenLive() {
                        if (typeof window.madaListenTenantContactMessages !== 'function') {
                            return;
                        }

                        this.echoChannel = window.madaListenTenantContactMessages({
                            tenantId: this.tenantId,
                            userId: this.userId,
                            onMessage: (payload) => this.handleIncoming(payload || {}),
                        });
                    },

                    handleIncoming(payload) {
                        if (! payload.thread_id) {
                            return;
                        }

                        if (this.folder === 'active') {
                            this.upsertThread(payload);
                        }

                        if (Number(this.selectedThreadId) === Number(payload.thread_id) && payload.body) {
                            this.appendMessageBubble({
                                id: payload.message_id,
                                body: payload.body,
                                sender_name: payload.sender_name,
                                avatar_url: payload.avatar_url,
                                is_staff: payload.sender_role === 'staff',
                                receipt: payload.receipt || 'delivered',
                            });
                            this.clearUnreadBadge(payload.thread_id);
                        }

                        if (window.madaPlayNotificationSound) {
                            window.madaPlayNotificationSound();
                        }
                    },

                    upsertThread(payload) {
                        const list = this.$refs.threadList;
                        if (! list || this.folder !== 'active') {
                            return;
                        }

                        list.querySelector('[data-thread-empty]')?.remove();

                        const existing = list.querySelector(`[data-thread-id="${payload.thread_id}"]`);
                        const showUrl = payload.show_url
                            || `{{ url('/app/contact-messages') }}/${payload.thread_id}`;
                        const archiveUrl = payload.archive_url
                            || `{{ url('/app/contact-messages') }}/${payload.thread_id}/archive`;
                        const unarchiveUrl = payload.unarchive_url
                            || `{{ url('/app/contact-messages') }}/${payload.thread_id}/unarchive`;
                        const destroyUrl = payload.destroy_url
                            || `{{ url('/app/contact-messages') }}/${payload.thread_id}`;

                        if (existing) {
                            const snippet = existing.querySelector('[data-thread-snippet]');
                            if (snippet && payload.snippet) {
                                snippet.textContent = payload.snippet;
                            }

                            if (Number(this.selectedThreadId) !== Number(payload.thread_id)) {
                                let badge = existing.querySelector('[data-thread-unread]');
                                if (! badge) {
                                    badge = document.createElement('span');
                                    badge.dataset.threadUnread = '';
                                    badge.className = 'inline-flex min-w-5 items-center justify-center rounded-full bg-brand-500 px-1.5 text-xs font-bold text-white';
                                    badge.textContent = '1';
                                    existing.querySelector('.mt-1\\.5')?.appendChild(badge);
                                } else {
                                    badge.textContent = String(Number(badge.textContent || '0') + 1);
                                }
                            }

                            list.prepend(existing);
                            return;
                        }

                        const row = this.buildThreadRow({
                            id: payload.thread_id,
                            display_name: payload.sender_name,
                            subject: payload.subject,
                            snippet: payload.snippet,
                            avatar_url: payload.avatar_url,
                            show_url: showUrl,
                            archive_url: archiveUrl,
                            unarchive_url: unarchiveUrl,
                            destroy_url: destroyUrl,
                            unread: true,
                            unread_count: 1,
                            is_archived: false,
                            last_message_at: new Date().toISOString(),
                        });

                        list.prepend(row);
                        this.bumpCounts({ active: 1 });

                        this.threads.unshift({
                            id: payload.thread_id,
                            subject: payload.subject,
                            snippet: payload.snippet,
                        });
                    },

                    buildThreadMenu(threadId, archiveUrl, unarchiveUrl, destroyUrl, isArchived = false) {
                        const wrap = document.createElement('div');
                        wrap.className = 'absolute end-2 top-3';
                        wrap.addEventListener('click', (event) => event.stopPropagation());

                        const toggle = document.createElement('button');
                        toggle.type = 'button';
                        toggle.className = 'rounded-lg p-1.5 text-mist-400 opacity-0 transition hover:bg-mist-100 hover:text-ink-700 group-hover:opacity-100 dark:hover:bg-ink-700 dark:hover:text-mist-200';
                        toggle.setAttribute('aria-label', 'إجراءات المحادثة');
                        toggle.innerHTML = '<svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" /></svg>';

                        const menu = document.createElement('div');
                        menu.className = 'absolute end-0 z-20 mt-1 hidden w-48 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-xl dark:border-ink-600 dark:bg-ink-800';
                        menu.innerHTML = isArchived
                            ? `
                                <button type="button" data-action="unarchive" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">إلغاء الأرشفة</button>
                                <button type="button" data-action="delete" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-danger-solid hover:bg-danger-solid/10">حذف</button>
                            `
                            : `
                                <button type="button" data-action="archive" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">أرشفة</button>
                                <button type="button" data-action="delete" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-danger-solid hover:bg-danger-solid/10">حذف</button>
                            `;

                        const hideAllMenus = () => {
                            this.$refs.threadList?.querySelectorAll('[data-live-menu]').forEach((node) => {
                                node.classList.add('hidden');
                            });
                        };

                        toggle.addEventListener('click', (event) => {
                            event.stopPropagation();
                            const isHidden = menu.classList.contains('hidden');
                            hideAllMenus();
                            if (isHidden) {
                                menu.classList.remove('hidden');
                                toggle.classList.add('opacity-100');
                            }
                        });

                        menu.dataset.liveMenu = '1';
                        menu.querySelector('[data-action="archive"]')?.addEventListener('click', (event) => {
                            event.stopPropagation();
                            menu.classList.add('hidden');
                            this.archiveThread(threadId, archiveUrl);
                        });
                        menu.querySelector('[data-action="unarchive"]')?.addEventListener('click', (event) => {
                            event.stopPropagation();
                            menu.classList.add('hidden');
                            this.unarchiveThread(threadId, unarchiveUrl);
                        });
                        menu.querySelector('[data-action="delete"]')?.addEventListener('click', (event) => {
                            event.stopPropagation();
                            menu.classList.add('hidden');
                            this.deleteThread(threadId, destroyUrl);
                        });

                        wrap.appendChild(toggle);
                        wrap.appendChild(menu);
                        return wrap;
                    },

                    appendMessageBubble(message) {
                        const list = this.$refs.chatPanel?.querySelector('[data-message-list]');
                        if (! list || list.querySelector(`[data-message-id="${message.id}"]`)) {
                            return;
                        }

                        list.insertAdjacentHTML('beforeend', this.messageBubbleHtml(message));
                        this.scrollMessages();
                    },

                    toastSuccess(message, options = {}) {
                        if (! window.Swal) {
                            return;
                        }

                        const undoUrl = options.undoUrl || null;

                        Swal.fire({
                            toast: true,
                            position: 'top-start',
                            icon: undoUrl ? 'warning' : 'success',
                            title: message,
                            showConfirmButton: Boolean(undoUrl),
                            confirmButtonText: options.undoLabel || 'تراجع',
                            confirmButtonColor: '#714b67',
                            showCancelButton: false,
                            timer: undoUrl ? 8000 : 2800,
                            timerProgressBar: true,
                        }).then((result) => {
                            if (! result.isConfirmed || ! undoUrl) {
                                return;
                            }

                            const form = document.createElement('form');
                            form.method = 'POST';
                            form.action = undoUrl;
                            form.style.display = 'none';

                            const csrf = document.createElement('input');
                            csrf.type = 'hidden';
                            csrf.name = '_token';
                            csrf.value = this.csrfToken();
                            form.appendChild(csrf);

                            const method = String(options.undoMethod || 'POST').toUpperCase();
                            if (method !== 'POST') {
                                const methodInput = document.createElement('input');
                                methodInput.type = 'hidden';
                                methodInput.name = '_method';
                                methodInput.value = method;
                                form.appendChild(methodInput);
                            }

                            document.body.appendChild(form);
                            form.submit();
                        });
                    },

                    toastError(message) {
                        if (! window.Swal) {
                            return;
                        }

                        Swal.fire({
                            toast: true,
                            position: 'top-start',
                            icon: 'error',
                            title: message,
                            showConfirmButton: false,
                            timer: 3200,
                            timerProgressBar: true,
                        });
                    },

                    escapeHtml(value) {
                        return String(value)
                            .replaceAll('&', '&amp;')
                            .replaceAll('<', '&lt;')
                            .replaceAll('>', '&gt;')
                            .replaceAll('"', '&quot;')
                            .replaceAll("'", '&#039;');
                    },

                    escapeAttr(value) {
                        return this.escapeHtml(value).replaceAll('`', '');
                    },
                }));
            });
        </script>
    @endpush
</x-layouts.app>
