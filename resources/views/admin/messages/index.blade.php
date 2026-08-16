@extends('layouts.admin')

@section('title', 'الرسائل والدعم الفني')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">التواصل</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الرسائل والدعم</span>
@endsection

@section('content')
    @php
        $closeUrl = route('admin.messages', ['status' => $activeStatus, 'q' => $search ?: null]);
        $lastMessageId = collect($selectedMessages)->max('id') ?? 0;
    @endphp

    <div
        x-data="madaMessagesInbox({
            closeUrl: @js($closeUrl),
            pollUrl: @js(route('admin.messages.poll')),
            csrf: @js(csrf_token()),
            status: @js($activeStatus),
            search: @js($search),
            selectedThreadId: @js($selected?->id),
            threads: @js($threads),
            counts: @js($counts),
            signature: @js($pollSignature),
            lastMessageId: @js((int) $lastMessageId),
            pollIntervalMs: 7000,
        })"
        @keydown.escape.window="closeChat()"
        class="space-y-0"
    >
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">الرسائل والدعم الفني</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">استفسارات نموذج التواصل وملّاك الحسابات — محادثة واحدة لكل عميل نشط.</p>
            </div>
        </div>

        <div class="mt-6 flex items-center gap-1 overflow-x-auto border-b border-mist-200 dark:border-ink-700" data-status-tabs>
            @foreach ($tabs as $key => $label)
                @php $isActive = $activeStatus === $key; @endphp
                <a
                    href="{{ route('admin.messages', ['status' => $key, 'q' => $search ?: null]) }}"
                    data-status-tab="{{ $key }}"
                    @class([
                        'flex shrink-0 items-center gap-2 border-b-2 px-3 py-2 text-sm font-medium transition-all duration-200',
                        'border-brand-500 text-brand-600 dark:text-brand-300' => $isActive,
                        'border-transparent text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200' => ! $isActive,
                    ])
                >
                    {{ $label }}
                    <span
                        data-status-count="{{ $key }}"
                        @class([
                            'rounded-md px-2 py-0.5 text-xs font-bold',
                            'bg-brand-500/15 text-brand-600 dark:text-brand-300' => $isActive,
                            'bg-mist-100 text-mist-500 dark:bg-ink-700 dark:text-mist-400' => ! $isActive,
                        ])
                    >{{ $counts[$key] }}</span>
                </a>
            @endforeach
        </div>

        <div class="mt-4 flex flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm lg:h-[calc(100dvh-15rem)] lg:flex-row dark:border-ink-600 dark:bg-ink-800">
            <div class="flex max-h-[42vh] shrink-0 flex-col border-b border-mist-200 lg:max-h-none lg:w-80 lg:border-b-0 lg:border-e dark:border-ink-700">
                <div class="border-b border-mist-100 p-3 dark:border-ink-700">
                    <form method="GET" action="{{ route('admin.messages') }}" class="relative">
                        <input type="hidden" name="status" value="{{ $activeStatus }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                        <input
                            type="search"
                            name="q"
                            value="{{ $search }}"
                            placeholder="ابحث بالاسم أو البريد أو الموضوع..."
                            class="w-full rounded-xl border border-mist-200 bg-white py-2 ps-9 pe-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                        >
                    </form>
                </div>

                <div class="flex-1 overflow-y-auto" data-thread-list x-ref="threadList">
                    @include('admin.messages._thread-list', ['threads' => $threads])
                </div>
            </div>

            <div class="flex min-w-0 flex-1 flex-col" data-chat-panel>
                @if ($selected)
                    <div data-chat-active class="flex h-full min-h-[20rem] flex-col">
                        <div class="flex items-center justify-between gap-3 border-b border-mist-100 p-4 dark:border-ink-700">
                            <div class="flex min-w-0 items-center gap-3">
                                <img
                                    src="{{ $selected->avatarUrl() }}"
                                    alt="{{ $selected->displayName() }}"
                                    class="h-10 w-10 shrink-0 rounded-full border border-slate-700 object-cover"
                                >
                                <div class="min-w-0">
                                    <p class="truncate font-display text-base font-medium text-ink-900 dark:text-ink-50">{{ $selected->subject }}</p>
                                    <p class="truncate text-xs text-mist-500 dark:text-mist-400">
                                        {{ $selected->displayName() }} · <span dir="ltr">{{ $selected->email }}</span>
                                    </p>
                                </div>
                            </div>

                            <div class="flex shrink-0 items-center gap-2">
                                <form method="POST" action="{{ route('admin.messages.status', $selected) }}">
                                    @csrf
                                    @method('PUT')
                                    <label class="sr-only" for="thread-status">حالة المحادثة</label>
                                    <select
                                        id="thread-status"
                                        name="status"
                                        onchange="this.form.submit()"
                                        class="rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm font-medium text-ink-700 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-mist-200"
                                    >
                                        @foreach ($tabs as $key => $label)
                                            <option value="{{ $key }}" @selected($selected->status === $key)>{{ $label }}</option>
                                        @endforeach
                                    </select>
                                </form>
                                <button
                                    type="button"
                                    @click="closeChat()"
                                    class="rounded-xl border border-mist-200 p-2 text-mist-500 transition hover:border-danger-solid/40 hover:bg-danger-solid/10 hover:text-danger-solid dark:border-ink-600"
                                    title="إغلاق المحادثة"
                                    aria-label="إغلاق المحادثة"
                                >
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.75"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                </button>
                            </div>
                        </div>

                        <div
                            data-message-list
                            x-ref="messageList"
                            class="flex-1 space-y-4 overflow-y-auto bg-neutral-50 p-4 sm:p-6 dark:bg-ink-900/50"
                        >
                            @foreach ($selectedMessages as $message)
                                @include('admin.messages._message-bubble', ['message' => $message])
                            @endforeach
                        </div>

                        <div class="border-t border-mist-100 p-3 dark:border-ink-700">
                            <form method="POST" action="{{ route('admin.messages.reply', $selected) }}" class="flex items-end gap-2">
                                @csrf
                                <textarea
                                    name="body"
                                    rows="1"
                                    required
                                    placeholder="اكتب ردًا..."
                                    class="max-h-32 min-h-[2.75rem] flex-1 resize-none rounded-xl border border-mist-200 bg-white px-3 py-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-brand-500 focus:outline-none focus:ring-2 focus:ring-brand-500/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                                ></textarea>
                                <button type="submit" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-brand-500 text-white shadow-glow transition duration-200 hover:bg-brand-600 active:scale-95" aria-label="إرسال">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                                </button>
                            </form>
                            <p class="mt-1.5 px-1 text-xs text-mist-400 dark:text-mist-500">Esc أو × لإغلاق المحادثة. الرد على محادثة مفتوحة ينقلها إلى «قيد المعالجة».</p>
                        </div>
                    </div>
                @else
                    <div data-chat-placeholder class="flex flex-1 flex-col items-center justify-center p-10 text-center">
                        <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-ink-700 dark:text-mist-500">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                        </span>
                        <p class="mt-3 text-sm font-medium text-ink-900 dark:text-ink-50">اختر محادثة لبدء القراءة</p>
                        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اختر رسالة من القائمة لقراءة التفاصيل والرد.</p>
                    </div>
                @endif
            </div>
        </div>
    </div>
@endsection

@push('scripts')
    <script>
        document.addEventListener('alpine:init', () => {
            Alpine.data('madaMessagesInbox', (config) => ({
                closeUrl: config.closeUrl,
                pollUrl: config.pollUrl,
                csrf: config.csrf,
                status: config.status,
                search: config.search,
                selectedThreadId: config.selectedThreadId,
                threads: config.threads || [],
                counts: config.counts || {},
                signature: config.signature || '',
                lastMessageId: config.lastMessageId || 0,
                pollIntervalMs: config.pollIntervalMs || 7000,
                openMenuId: null,
                relativeTimer: null,
                pollTimer: null,
                polling: false,

                get hasSelected() {
                    return Boolean(this.selectedThreadId);
                },

                init() {
                    this.refreshRelativeTimes();
                    this.relativeTimer = setInterval(() => this.refreshRelativeTimes(), 60000);
                    this.pollTimer = setInterval(() => this.poll(), this.pollIntervalMs);
                    document.addEventListener('visibilitychange', () => {
                        if (! document.hidden) {
                            this.poll();
                        }
                    });
                },

                destroy() {
                    if (this.relativeTimer) {
                        clearInterval(this.relativeTimer);
                    }
                    if (this.pollTimer) {
                        clearInterval(this.pollTimer);
                    }
                },

                toggleMenu(threadId) {
                    this.openMenuId = this.openMenuId === threadId ? null : threadId;
                },

                closeChat() {
                    if (! this.hasSelected) {
                        return;
                    }
                    window.location.href = this.closeUrl;
                },

                closeChatLocally() {
                    this.selectedThreadId = null;
                    this.lastMessageId = 0;

                    const panel = this.$el.querySelector('[data-chat-panel]');
                    if (! panel) {
                        history.replaceState({}, '', this.closeUrl);
                        return;
                    }

                    panel.innerHTML = `
                        <div data-chat-placeholder class="flex flex-1 flex-col items-center justify-center p-10 text-center">
                            <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-ink-700 dark:text-mist-500">
                                <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                            </span>
                            <p class="mt-3 text-sm font-medium text-ink-900 dark:text-ink-50">اختر محادثة لبدء القراءة</p>
                            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اختر رسالة من القائمة لقراءة التفاصيل والرد.</p>
                        </div>
                    `;

                    history.replaceState({}, '', this.closeUrl);
                },

                formatRelative(iso) {
                    if (! iso) {
                        return '';
                    }

                    const then = new Date(iso).getTime();
                    if (Number.isNaN(then)) {
                        return '';
                    }

                    const rtf = new Intl.RelativeTimeFormat('ar', { numeric: 'auto' });
                    const diffSec = Math.round((then - Date.now()) / 1000);
                    const abs = Math.abs(diffSec);
                    let value = diffSec;
                    let unit = 'second';

                    if (abs >= 60 && abs < 3600) {
                        value = Math.round(diffSec / 60);
                        unit = 'minute';
                    } else if (abs >= 3600 && abs < 86400) {
                        value = Math.round(diffSec / 3600);
                        unit = 'hour';
                    } else if (abs >= 86400 && abs < 604800) {
                        value = Math.round(diffSec / 86400);
                        unit = 'day';
                    } else if (abs >= 604800 && abs < 2629800) {
                        value = Math.round(diffSec / 604800);
                        unit = 'week';
                    } else if (abs >= 2629800 && abs < 31557600) {
                        value = Math.round(diffSec / 2629800);
                        unit = 'month';
                    } else if (abs >= 31557600) {
                        value = Math.round(diffSec / 31557600);
                        unit = 'year';
                    }

                    return rtf.format(value, unit);
                },

                refreshRelativeTimes() {
                    this.$el.querySelectorAll('.mada-relative-time[data-timestamp]').forEach((node) => {
                        const iso = node.getAttribute('data-timestamp');
                        if (iso) {
                            node.textContent = this.formatRelative(iso);
                        }
                    });
                },

                escapeHtml(value) {
                    return String(value ?? '')
                        .replace(/&/g, '&amp;')
                        .replace(/</g, '&lt;')
                        .replace(/>/g, '&gt;')
                        .replace(/"/g, '&quot;')
                        .replace(/'/g, '&#039;');
                },

                receiptMarkup(status, onDark) {
                    const label = status === 'read' ? 'تمت القراءة' : (status === 'delivered' ? 'تم التسليم' : 'تم الإرسال');
                    const color = status === 'read'
                        ? 'text-sky-500'
                        : (onDark ? 'text-white/70' : 'text-mist-400');
                    const second = status === 'pending'
                        ? ''
                        : `<svg xmlns="http://www.w3.org/2000/svg" class="-ms-2 h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>`;

                    return `<span class="inline-flex ${color}" title="${label}" aria-label="${label}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-3.5 w-3.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                        ${second}
                    </span>`;
                },

                renderThreadList(threads) {
                    if (! threads.length) {
                        return `<div class="p-8 text-center">
                            <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد محادثات</p>
                            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">لا توجد رسائل في هذه الحالة.</p>
                        </div>`;
                    }

                    return threads.map((thread) => {
                        const selected = thread.is_selected;
                        const rowClass = selected
                            ? 'bg-brand-500/[0.06] border-s-2 border-s-brand-500'
                            : 'hover:bg-mist-50 dark:hover:bg-ink-700/40';
                        const archive = thread.can_archive
                            ? `<form method="POST" action="${this.escapeHtml(thread.archive_url)}">
                                    <input type="hidden" name="_token" value="${this.escapeHtml(this.csrf)}">
                                    <button type="submit" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-ink-600 hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">أرشفة</button>
                               </form>`
                            : '';

                        return `<div
                            id="mada-search-thread-${thread.id}"
                            data-thread-id="${thread.id}"
                            data-mada-search="thread-${thread.id}"
                            class="group relative flex gap-3 border-b border-mist-100 p-4 transition duration-150 dark:border-ink-700 ${rowClass}"
                        >
                            <a href="${this.escapeHtml(thread.open_url)}" class="flex min-w-0 flex-1 gap-3">
                                <img src="${this.escapeHtml(thread.avatar_url)}" alt="${this.escapeHtml(thread.display_name)}" class="h-10 w-10 shrink-0 rounded-full border border-slate-700 object-cover">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center justify-between gap-2 pe-8">
                                        <p class="truncate text-sm font-semibold text-ink-900 dark:text-ink-50">${this.escapeHtml(thread.display_name)}</p>
                                        <span class="mada-relative-time shrink-0 text-xs text-mist-400 dark:text-mist-500" data-timestamp="${this.escapeHtml(thread.last_message_at || '')}">${this.escapeHtml(this.formatRelative(thread.last_message_at))}</span>
                                    </div>
                                    <p class="mt-0.5 truncate text-sm font-medium text-ink-700 dark:text-mist-200">${this.escapeHtml(thread.subject)}</p>
                                    <p class="mt-0.5 truncate text-xs text-mist-500 dark:text-mist-400">${this.escapeHtml(thread.snippet)}</p>
                                    <div class="mt-1.5 flex items-center gap-2">
                                        <span class="inline-flex items-center gap-1 rounded-md px-2 py-0.5 text-xs font-medium ${this.escapeHtml(thread.status_badge)}">
                                            <span class="h-1.5 w-1.5 rounded-md ${this.escapeHtml(thread.status_dot)}"></span>
                                            ${this.escapeHtml(thread.status_label)}
                                        </span>
                                        ${thread.unread ? '<span class="h-2 w-2 rounded-full bg-brand-500"></span>' : ''}
                                    </div>
                                </div>
                            </a>
                            <div class="absolute end-2 top-3">
                                <button type="button" @click.stop="toggleMenu(${thread.id})" class="rounded-lg p-1.5 text-mist-400 opacity-0 transition hover:bg-mist-100 hover:text-ink-700 group-hover:opacity-100 dark:hover:bg-ink-700 dark:hover:text-mist-200" :class="openMenuId === ${thread.id} && 'opacity-100'" aria-label="إجراءات المحادثة">
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="currentColor"><path d="M12 6.75a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Zm0 6a.75.75 0 1 1 0-1.5.75.75 0 0 1 0 1.5Z" /></svg>
                                </button>
                                <div x-show="openMenuId === ${thread.id}" x-cloak @click.outside="openMenuId = null" x-transition class="absolute end-0 z-20 mt-1 w-40 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-xl dark:border-ink-600 dark:bg-ink-800">
                                    ${archive}
                                    <button type="button" data-delete-thread="${thread.id}" class="flex w-full items-center gap-2 px-3 py-2 text-start text-sm text-danger-solid hover:bg-danger-solid/10">حذف</button>
                                </div>
                            </div>
                        </div>`;
                    }).join('');
                },

                renderMessageBubble(message) {
                    const isAdmin = Boolean(message.is_admin);
                    const bubbleClass = isAdmin
                        ? 'bg-brand-500 text-white rounded-se-none'
                        : 'border border-mist-200 bg-white text-ink-700 rounded-ss-none dark:border-ink-700 dark:bg-ink-800 dark:text-mist-100';
                    const rowClass = isAdmin ? 'flex-row-reverse' : 'flex-row';
                    const metaClass = isAdmin ? 'justify-end' : 'justify-start';

                    return `<div class="flex items-end gap-2 ${rowClass}" data-message-id="${message.id}">
                        <img src="${this.escapeHtml(message.avatar_url)}" alt="${this.escapeHtml(message.sender_name)}" class="h-8 w-8 shrink-0 rounded-full border border-slate-700 object-cover">
                        <div class="max-w-[80%]">
                            <div class="px-4 py-3 text-sm shadow-sm rounded-2xl ${bubbleClass}">${this.escapeHtml(message.body)}</div>
                            <div class="mt-1 flex items-center gap-1.5 px-1 text-xs text-mist-400 dark:text-mist-500 ${metaClass}">
                                <span>
                                    ${this.escapeHtml(message.sender_name)} ·
                                    <span class="mada-relative-time" data-timestamp="${this.escapeHtml(message.created_at || '')}">${this.escapeHtml(this.formatRelative(message.created_at))}</span>
                                </span>
                                ${this.receiptMarkup(message.receipt, isAdmin)}
                            </div>
                        </div>
                    </div>`;
                },

                bindThreadListEvents() {
                    const list = this.$refs.threadList;
                    if (! list || list.dataset.boundDelete === '1') {
                        // Re-init Alpine on replaced HTML.
                    }

                    list.querySelectorAll('[data-delete-thread]').forEach((button) => {
                        button.addEventListener('click', (event) => {
                            event.preventDefault();
                            event.stopPropagation();
                            const id = Number(button.getAttribute('data-delete-thread'));
                            const thread = this.threads.find((item) => item.id === id);
                            if (thread) {
                                this.openMenuId = null;
                                this.deleteThread(thread);
                            }
                        });
                    });
                },

                applyThreadList(threads) {
                    this.threads = threads;
                    const list = this.$refs.threadList;
                    if (! list) {
                        return;
                    }

                    list.innerHTML = this.renderThreadList(threads);
                    this.$nextTick(() => {
                        if (window.Alpine) {
                            Alpine.initTree(list);
                        }
                        this.bindThreadListEvents();
                        this.refreshRelativeTimes();
                    });
                },

                appendMessages(messages) {
                    const list = this.$refs.messageList || this.$el.querySelector('[data-message-list]');
                    if (! list || ! messages.length) {
                        return;
                    }

                    const html = messages
                        .filter((message) => message.id > this.lastMessageId)
                        .map((message) => this.renderMessageBubble(message))
                        .join('');

                    if (! html) {
                        return;
                    }

                    list.insertAdjacentHTML('beforeend', html);
                    this.lastMessageId = Math.max(
                        this.lastMessageId,
                        ...messages.map((message) => message.id)
                    );
                    list.scrollTop = list.scrollHeight;
                    this.refreshRelativeTimes();
                },

                updateCounts(counts) {
                    this.counts = counts || this.counts;
                    Object.entries(this.counts).forEach(([key, value]) => {
                        const node = this.$el.querySelector(`[data-status-count="${key}"]`);
                        if (node) {
                            node.textContent = String(value);
                        }
                    });
                },

                async poll() {
                    if (this.polling || document.hidden) {
                        return;
                    }

                    this.polling = true;

                    try {
                        const params = new URLSearchParams({
                            status: this.status,
                            after_message_id: String(this.lastMessageId || 0),
                        });

                        if (this.search) {
                            params.set('q', this.search);
                        }

                        if (this.selectedThreadId) {
                            params.set('thread', String(this.selectedThreadId));
                        }

                        const response = await fetch(`${this.pollUrl}?${params.toString()}`, {
                            headers: {
                                'Accept': 'application/json',
                                'X-Requested-With': 'XMLHttpRequest',
                            },
                        });

                        if (! response.ok) {
                            return;
                        }

                        const data = await response.json();
                        this.updateCounts(data.counts);

                        if (data.signature && data.signature !== this.signature) {
                            this.signature = data.signature;
                            this.applyThreadList(data.threads || []);
                        }

                        if (this.selectedThreadId && data.selected_exists === false) {
                            this.closeChatLocally();
                            return;
                        }

                        if (Array.isArray(data.messages) && data.messages.length) {
                            this.appendMessages(data.messages);
                        }
                    } catch (error) {
                        // Ignore transient poll errors.
                    } finally {
                        this.polling = false;
                    }
                },

                deleteThread(thread) {
                    if (typeof Swal === 'undefined') {
                        return;
                    }

                    Swal.fire({
                        title: 'هل أنت تأكد من حذف هذه المحادثة؟',
                        text: 'سيتم الحذف الناعم (Soft Delete) ويمكن استرجاعها لاحقًا من قاعدة البيانات.',
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
                            const response = await fetch(thread.destroy_url, {
                                method: 'DELETE',
                                headers: {
                                    'X-CSRF-TOKEN': this.csrf,
                                    'Accept': 'application/json',
                                    'X-Requested-With': 'XMLHttpRequest',
                                },
                            });

                            if (! response.ok) {
                                throw new Error('Delete failed');
                            }

                            const data = await response.json();
                            const deletedId = thread.id;
                            const row = this.$el.querySelector(`[data-thread-id="${deletedId}"]`);

                            if (row) {
                                row.classList.add('opacity-0', 'transition', 'duration-200');
                                setTimeout(() => row.remove(), 200);
                            }

                            this.threads = this.threads.filter((item) => item.id !== deletedId);

                            if (this.counts[this.status] > 0) {
                                this.counts[this.status] -= 1;
                                this.updateCounts(this.counts);
                            }

                            Swal.fire({
                                toast: true,
                                position: 'top-end',
                                icon: 'success',
                                title: data.message || 'تم حذف المحادثة بنجاح.',
                                showConfirmButton: Boolean(data.undo_url),
                                confirmButtonText: data.undo_label || 'تراجع',
                                confirmButtonColor: '#714b67',
                                timer: data.undo_url ? 8000 : 2800,
                                timerProgressBar: true,
                            }).then((toastResult) => {
                                if (! toastResult.isConfirmed || ! data.undo_url) {
                                    return;
                                }

                                const form = document.createElement('form');
                                form.method = 'POST';
                                form.action = data.undo_url;
                                form.style.display = 'none';

                                const csrf = document.createElement('input');
                                csrf.type = 'hidden';
                                csrf.name = '_token';
                                csrf.value = this.csrf;
                                form.appendChild(csrf);

                                document.body.appendChild(form);
                                form.submit();
                            });

                            if (this.selectedThreadId === deletedId) {
                                this.closeChatLocally();
                            }
                        } catch (error) {
                            Swal.fire({
                                icon: 'error',
                                title: 'تعذّر حذف المحادثة',
                                confirmButtonColor: '#714b67',
                            });
                        }
                    });
                },
            }));
        });
    </script>
@endpush
