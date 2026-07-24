@extends('layouts.admin')

@section('title', 'الرسائل والدعم الفني')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">التواصل</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الرسائل والدعم</span>
@endsection

@section('content')
    @php
        $statusMeta = [
            'open' => ['label' => 'مفتوح', 'badge' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400', 'dot' => 'bg-amber-500'],
            'in_progress' => ['label' => 'قيد المعالجة', 'badge' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400', 'dot' => 'bg-sky-500'],
            'resolved' => ['label' => 'تم الحل', 'badge' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', 'dot' => 'bg-emerald-400'],
        ];
    @endphp

    {{-- Header --}}
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">الرسائل والدعم الفني</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">استفسارات ملّاك الحسابات — فتح المحادثة قراءة موثّقة عبر المستأجرين.</p>
        </div>
    </div>

    {{-- Status filter tabs --}}
    <div class="mt-6 flex items-center gap-1 border-b border-mist-200 dark:border-ink-700">
        @foreach ($tabs as $key => $label)
            @php $isActive = $activeStatus === $key; @endphp
            <a
                href="{{ route('admin.messages', ['status' => $key]) }}"
                @class([
                    'flex items-center gap-2 border-b-2 px-4 py-2.5 text-sm font-medium transition-all duration-200',
                    'border-emerald-400 text-emerald-600 dark:text-emerald-400' => $isActive,
                    'border-transparent text-mist-500 hover:text-ink-700 dark:text-mist-400 dark:hover:text-mist-200' => ! $isActive,
                ])
            >
                {{ $label }}
                <span @class([
                    'rounded-full px-2 py-0.5 text-xs font-bold',
                    'bg-emerald-400/15 text-emerald-600 dark:text-emerald-400' => $isActive,
                    'bg-mist-100 text-mist-500 dark:bg-ink-700 dark:text-mist-400' => ! $isActive,
                ])>{{ $counts[$key] }}</span>
            </a>
        @endforeach
    </div>

    {{-- Inbox: two-pane on desktop, stacked on mobile --}}
    <div class="mt-4 flex flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm lg:h-[calc(100dvh-15rem)] lg:flex-row dark:border-ink-600 dark:bg-ink-800">
        {{-- Start pane: thread list --}}
        <div class="flex shrink-0 flex-col border-b border-mist-200 lg:w-80 lg:border-b-0 lg:border-e dark:border-ink-700">
            <div class="border-b border-mist-100 p-3 dark:border-ink-700">
                <div class="relative">
                    <svg xmlns="http://www.w3.org/2000/svg" class="pointer-events-none absolute inset-y-0 start-3 my-auto h-4 w-4 text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m21 21-5.197-5.197m0 0A7.5 7.5 0 1 0 5.196 5.196a7.5 7.5 0 0 0 10.607 10.607Z" /></svg>
                    <input type="search" placeholder="ابحث بالمستأجر أو الموضوع..." class="w-full rounded-xl border border-mist-200 bg-white py-2 ps-9 pe-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                </div>
            </div>

            <div class="flex-1 overflow-y-auto">
                @forelse ($threads as $thread)
                    @php $isSelected = $selected && $selected['id'] === $thread['id']; @endphp
                    <a
                        href="{{ route('admin.messages', ['status' => $activeStatus, 'thread' => $thread['id']]) }}"
                        @class([
                            'flex gap-3 border-b border-mist-100 p-4 transition duration-150 dark:border-ink-700',
                            'bg-emerald-400/[0.06] border-s-2 border-s-emerald-400' => $isSelected,
                            'hover:bg-mist-50 dark:hover:bg-ink-700/40' => ! $isSelected,
                        ])
                    >
                        <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-mist-100 font-display text-sm font-bold text-mist-500 dark:bg-ink-700 dark:text-mist-300">
                            {{ mb_substr($thread['tenant'], 0, 1) }}
                        </span>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center justify-between gap-2">
                                <p class="truncate text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $thread['tenant'] }}</p>
                                <span class="shrink-0 text-[11px] text-mist-400 dark:text-mist-500">{{ $thread['time'] }}</span>
                            </div>
                            <p class="mt-0.5 truncate text-sm font-medium text-ink-700 dark:text-mist-200">{{ $thread['subject'] }}</p>
                            <p class="mt-0.5 truncate text-xs text-mist-500 dark:text-mist-400">{{ $thread['snippet'] }}</p>
                            <div class="mt-1.5 flex items-center gap-2">
                                <span class="inline-flex items-center gap-1 rounded-full px-2 py-0.5 text-[10px] font-medium {{ $statusMeta[$thread['status']]['badge'] }}">
                                    <span class="h-1.5 w-1.5 rounded-full {{ $statusMeta[$thread['status']]['dot'] }}"></span>
                                    {{ $statusMeta[$thread['status']]['label'] }}
                                </span>
                                @if ($thread['unread'])
                                    <span class="h-2 w-2 rounded-full bg-emerald-400"></span>
                                @endif
                            </div>
                        </div>
                    </a>
                @empty
                    <div class="p-8 text-center">
                        <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد محادثات</p>
                        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">لا توجد رسائل في هذه الحالة.</p>
                    </div>
                @endforelse
            </div>
        </div>

        {{-- End pane: conversation --}}
        <div class="flex min-w-0 flex-1 flex-col">
            @if ($selected)
                {{-- Thread header with status dropdown --}}
                <div
                    x-data="{
                        open: false,
                        status: '{{ $selected['status'] }}',
                        labels: { open: 'مفتوح', in_progress: 'قيد المعالجة', resolved: 'تم الحل' },
                    }"
                    class="flex items-center justify-between gap-3 border-b border-mist-100 p-4 dark:border-ink-700"
                >
                    <div class="min-w-0">
                        <p class="truncate font-display text-base font-semibold text-ink-900 dark:text-ink-50">{{ $selected['subject'] }}</p>
                        <p class="truncate text-xs text-mist-500 dark:text-mist-400">{{ $selected['tenant'] }}</p>
                    </div>

                    <div class="relative shrink-0" @click.outside="open = false">
                        <button type="button" @click="open = !open" class="inline-flex items-center gap-2 rounded-xl border border-mist-200 px-3 py-2 text-sm font-medium text-ink-700 transition hover:border-emerald-400 dark:border-ink-600 dark:text-mist-200">
                            <span class="h-2 w-2 rounded-full" :class="{ 'bg-amber-500': status === 'open', 'bg-sky-500': status === 'in_progress', 'bg-emerald-400': status === 'resolved' }"></span>
                            <span x-text="labels[status]"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 text-mist-400" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m19.5 8.25-7.5 7.5-7.5-7.5" /></svg>
                        </button>

                        <div
                            x-show="open"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 -translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            class="absolute end-0 mt-2 w-44 overflow-hidden rounded-xl border border-mist-200 bg-white py-1 shadow-xl dark:border-ink-600 dark:bg-ink-800"
                        >
                            <template x-for="key in ['open', 'in_progress', 'resolved']" :key="key">
                                <button type="button" @click="status = key; open = false" class="flex w-full items-center gap-2 px-4 py-2 text-start text-sm text-ink-600 transition hover:bg-mist-100 dark:text-mist-300 dark:hover:bg-ink-700">
                                    <span class="h-2 w-2 rounded-full" :class="{ 'bg-amber-500': key === 'open', 'bg-sky-500': key === 'in_progress', 'bg-emerald-400': key === 'resolved' }"></span>
                                    <span x-text="labels[key]"></span>
                                </button>
                            </template>
                        </div>
                    </div>
                </div>

                {{-- Messages --}}
                <div class="flex-1 space-y-4 overflow-y-auto bg-neutral-50 p-4 sm:p-6 dark:bg-ink-900/50">
                    @foreach ($selected['messages'] as $message)
                        @php $isAdmin = $message['from'] === 'admin'; @endphp
                        <div class="flex {{ $isAdmin ? 'justify-end' : 'justify-start' }}">
                            <div class="max-w-[80%]">
                                <div @class([
                                    'px-4 py-3 text-sm shadow-sm rounded-2xl',
                                    'bg-emerald-400 text-emerald-900 rounded-se-none' => $isAdmin,
                                    'border border-mist-200 bg-white text-ink-700 rounded-ss-none dark:border-ink-700 dark:bg-ink-800 dark:text-mist-100' => ! $isAdmin,
                                ])>
                                    {{ $message['body'] }}
                                </div>
                                <p class="mt-1 px-1 text-[11px] text-mist-400 dark:text-mist-500 {{ $isAdmin ? 'text-end' : 'text-start' }}">
                                    {{ $message['name'] }} · {{ $message['time'] }}
                                </p>
                            </div>
                        </div>
                    @endforeach
                </div>

                {{-- Quick reply composer --}}
                <div class="border-t border-mist-100 p-3 dark:border-ink-700">
                    <div class="flex items-end gap-2">
                        <textarea rows="1" placeholder="اكتب ردًا..." class="max-h-32 min-h-[2.75rem] flex-1 resize-none rounded-xl border border-mist-200 bg-white px-3 py-3 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"></textarea>
                        <button type="button" class="flex h-11 w-11 shrink-0 items-center justify-center rounded-xl bg-emerald-400 text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95" aria-label="إرسال">
                            <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 12 3.269 3.125A59.769 59.769 0 0 1 21.485 12 59.768 59.768 0 0 1 3.27 20.875L5.999 12Zm0 0h7.5" /></svg>
                        </button>
                    </div>
                    <p class="mt-1.5 px-1 text-[11px] text-mist-400 dark:text-mist-500">الرد على محادثة مفتوحة ينقلها تلقائيًا إلى «قيد المعالجة».</p>
                </div>
            @else
                {{-- No thread selected --}}
                <div class="flex flex-1 flex-col items-center justify-center p-10 text-center">
                    <span class="flex h-14 w-14 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-ink-700 dark:text-mist-500">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-7 w-7" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018Z" /></svg>
                    </span>
                    <p class="mt-3 text-sm font-medium text-ink-900 dark:text-ink-50">اختر محادثة لعرضها</p>
                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">اختر رسالة من القائمة لقراءة التفاصيل والرد.</p>
                </div>
            @endif
        </div>
    </div>
@endsection
