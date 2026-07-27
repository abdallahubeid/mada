@extends('layouts.admin')

@section('title', 'مركز الإشعارات')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">التواصل</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">الإشعارات</span>
@endsection

@section('content')
    @php
        $meta = [
            'approval' => ['tone' => 'bg-emerald-400/15 text-emerald-500 dark:text-emerald-400', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />'],
            'security' => ['tone' => 'bg-amber-500/15 text-amber-500', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75m-3-7.036A11.959 11.959 0 0 1 3.598 6 11.99 11.99 0 0 0 3 9.749c0 5.592 3.824 10.29 9 11.623 5.176-1.332 9-6.03 9-11.622 0-1.31-.21-2.571-.598-3.751h-.152c-3.196 0-6.1-1.248-8.25-3.285Z" />'],
            'job_failed' => ['tone' => 'bg-danger-solid/15 text-danger-solid', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m9-.75a9 9 0 1 1-18 0 9 9 0 0 1 18 0Zm-9 3.75h.008v.008H12v-.008Z" />'],
            'ops' => ['tone' => 'bg-sky-500/15 text-sky-500', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M7.5 8.25h9m-9 3H12m-9.75 1.51c0 1.6 1.123 2.994 2.707 3.227 1.129.166 2.27.293 3.423.379.35.026.67.21.865.501L12 21l2.755-4.133a1.14 1.14 0 0 1 .865-.501 48.172 48.172 0 0 0 3.423-.379c1.584-.233 2.707-1.626 2.707-3.228V6.741c0-1.602-1.123-2.995-2.707-3.228A48.394 48.394 0 0 0 12 3c-2.392 0-4.744.175-7.043.513C3.373 3.746 2.25 5.14 2.25 6.741v6.018z" />'],
            'plan_limit' => ['tone' => 'bg-sky-500/15 text-sky-500', 'icon' => '<path stroke-linecap="round" stroke-linejoin="round" d="M3 13.125C3 12.504 3.504 12 4.125 12h2.25c.621 0 1.125.504 1.125 1.125v6.75C7.5 20.496 6.996 21 6.375 21h-2.25A1.125 1.125 0 0 1 3 19.875v-6.75ZM9.75 8.625c0-.621.504-1.125 1.125-1.125h2.25c.621 0 1.125.504 1.125 1.125v11.25c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V8.625ZM16.5 4.125c0-.621.504-1.125 1.125-1.125h2.25C20.496 3 21 3.504 21 4.125v15.75c0 .621-.504 1.125-1.125 1.125h-2.25a1.125 1.125 0 0 1-1.125-1.125V4.125Z" />'],
        ];
        $totalFiltered = array_sum(array_map(fn ($g): int => count($g['items']), $groups));
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <div class="flex items-center gap-2">
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">مركز الإشعارات</h2>
                @if ($unreadCounts['all'] > 0)
                    <span class="rounded-full bg-emerald-400 px-2 py-0.5 text-xs font-bold text-emerald-900">{{ $unreadCounts['all'] }} غير مقروء</span>
                @endif
            </div>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تنبيهات المنصّة على مستوى النظام — تُحفظ في قاعدة البيانات وتُبثّ العاجلة عبر Reverb.</p>
        </div>

        <div class="flex items-center gap-2">
            <form method="POST" action="{{ route('admin.notifications.read-all') }}">
                @csrf
                <button type="submit" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition duration-200 hover:border-emerald-400 hover:text-emerald-600 active:scale-[0.98] dark:border-ink-600 dark:text-mist-200 dark:hover:text-emerald-400">
                    تحديد الكل كمقروء
                </button>
            </form>
            <form
                method="POST"
                action="{{ route('admin.notifications.destroy-all') }}"
                x-data
                @submit.prevent="
                    Swal.fire({
                        title: 'مسح كل الإشعارات؟',
                        text: 'لا يمكن التراجع عن هذا الإجراء.',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonColor: '#ef4444',
                        cancelButtonColor: '#64748b',
                        confirmButtonText: 'نعم، امسح',
                        cancelButtonText: 'إلغاء',
                    }).then((result) => { if (result.isConfirmed) $el.submit(); });
                "
            >
                @csrf
                @method('DELETE')
                <button type="submit" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-mist-600 transition duration-200 hover:border-danger-solid hover:text-danger-solid active:scale-[0.98] dark:border-ink-600 dark:text-mist-300">
                    مسح الكل
                </button>
            </form>
        </div>
    </div>

    <div class="mt-6 flex flex-wrap items-center gap-2">
        @foreach ($categories as $key => $cat)
            @php $isActive = $activeCategory === $key; @endphp
            @if ($cat['phase4'])
                <span class="inline-flex cursor-not-allowed items-center gap-2 rounded-lg border border-dashed border-mist-200 bg-white px-3 py-1.5 text-sm font-medium text-mist-400 transition-colors dark:border-ink-700 dark:bg-ink-800 dark:text-mist-500" title="يتوفّر في المرحلة 4">
                    {{ $cat['label'] }}
                    <span class="rounded-full bg-mist-100 px-1.5 py-0.5 text-xs font-semibold uppercase tracking-wide text-mist-500 dark:bg-ink-900/60 dark:text-mist-400">Phase 4</span>
                </span>
            @else
                <a
                    href="{{ route('admin.notifications', ['category' => $key]) }}"
                    @class([
                        'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors',
                        'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $isActive,
                        'border-mist-200 bg-white text-mist-500 hover:text-ink-700 dark:border-ink-700 dark:bg-ink-800 dark:text-mist-400 dark:hover:text-mist-100' => ! $isActive,
                    ])
                >
                    {{ $cat['label'] }}
                    @if (($unreadCounts[$key] ?? 0) > 0)
                        <span @class([
                            'rounded-full px-1.5 py-0.5 text-xs font-bold',
                            'bg-emerald-500/20 text-emerald-700 dark:text-emerald-300' => $isActive,
                            'bg-mist-100 text-mist-600 dark:bg-ink-900/60 dark:text-mist-300' => ! $isActive,
                        ])>{{ $unreadCounts[$key] }}</span>
                    @endif
                </a>
            @endif
        @endforeach
    </div>

    <div class="mt-6 space-y-6">
        @if ($totalFiltered === 0)
            <div class="rounded-2xl border border-dashed border-mist-200 py-16 text-center dark:border-ink-600">
                <span class="mx-auto flex h-12 w-12 items-center justify-center rounded-2xl bg-mist-100 text-mist-400 dark:bg-ink-700 dark:text-mist-500">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-6 w-6" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M14.857 17.082a23.848 23.848 0 0 0 5.454-1.31A8.967 8.967 0 0 1 18 9.75V9A6 6 0 0 0 6 9v.75a8.967 8.967 0 0 1-2.312 6.022c1.733.64 3.56 1.085 5.455 1.31m5.714 0a24.255 24.255 0 0 1-5.714 0m5.714 0a3 3 0 1 1-5.714 0" /></svg>
                </span>
                <p class="mt-3 text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد إشعارات</p>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">ستظهر تنبيهات هذه الفئة هنا عند توفّرها.</p>
            </div>
        @else
            @foreach ($groups as $group)
                @if (count($group['items']) > 0)
                    <div>
                        <h3 class="px-1 text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-mist-400">{{ $group['label'] }}</h3>
                        <ul class="mt-2 space-y-2">
                            @foreach ($group['items'] as $n)
                                @php
                                    $tone = $meta[$n['category']]['tone'] ?? $meta['ops']['tone'];
                                    $icon = $meta[$n['category']]['icon'] ?? $meta['ops']['icon'];
                                @endphp
                                <li
                                    @class([
                                        'group flex items-start gap-3 rounded-2xl border p-4 shadow-sm transition duration-200',
                                        'border-mist-200 bg-white dark:border-ink-600 dark:bg-ink-800' => $n['read'],
                                        'border-emerald-400/30 bg-emerald-400/[0.03] dark:bg-ink-800' => ! $n['read'],
                                    ])
                                >
                                    <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl {{ $tone }}">
                                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">{!! $icon !!}</svg>
                                    </span>

                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            @unless ($n['read'])
                                                <span class="h-2 w-2 shrink-0 rounded-full bg-emerald-400"></span>
                                            @endunless
                                            <p class="truncate text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $n['title'] }}</p>
                                        </div>
                                        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $n['body'] }}</p>
                                        <div class="mt-2 flex items-center gap-3">
                                            <span class="text-xs text-mist-400 dark:text-mist-500">{{ $n['time'] }}</span>
                                            @if (! empty($n['target_url']))
                                                <a href="{{ $n['target_url'] }}" class="text-xs font-medium text-emerald-600 hover:underline dark:text-emerald-400">الانتقال</a>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="flex shrink-0 items-center gap-1">
                                        <form method="POST" action="{{ route('admin.notifications.toggle-read', $n['id']) }}">
                                            @csrf
                                            @method('PUT')
                                            <button
                                                type="submit"
                                                class="rounded-lg p-1.5 text-mist-400 transition hover:bg-mist-100 hover:text-emerald-600 active:scale-90 dark:hover:bg-ink-700 dark:hover:text-emerald-400"
                                                title="{{ $n['read'] ? 'تحديد كغير مقروء' : 'تحديد كمقروء' }}"
                                            >
                                                @if ($n['read'])
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.98 8.223A10.477 10.477 0 0 0 1.934 12C3.226 16.338 7.244 19.5 12 19.5c.993 0 1.953-.138 2.863-.395M6.228 6.228A10.451 10.451 0 0 1 12 4.5c4.756 0 8.773 3.162 10.065 7.498a10.522 10.522 0 0 1-4.293 5.774M6.228 6.228 3 3m3.228 3.228 3.65 3.65m7.894 7.894L21 21m-3.228-3.228-3.65-3.65m0 0a3 3 0 1 0-4.243-4.243m4.243 4.243L9.88 9.88" /></svg>
                                                @else
                                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="m4.5 12.75 6 6 9-13.5" /></svg>
                                                @endif
                                            </button>
                                        </form>
                                        <form method="POST" action="{{ route('admin.notifications.destroy', $n['id']) }}">
                                            @csrf
                                            @method('DELETE')
                                            <button
                                                type="submit"
                                                class="rounded-lg p-1.5 text-mist-400 transition hover:bg-mist-100 hover:text-danger-solid active:scale-90 dark:hover:bg-ink-700"
                                                title="حذف"
                                            >
                                                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                                            </button>
                                        </form>
                                    </div>
                                </li>
                            @endforeach
                        </ul>
                    </div>
                @endif
            @endforeach
        @endif
    </div>
@endsection
