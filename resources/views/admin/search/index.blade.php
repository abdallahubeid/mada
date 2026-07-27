@extends('layouts.admin')

@section('title', 'نتائج البحث')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">المنصّة</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">نتائج البحث</span>
@endsection

@section('content')
    <div class="mx-auto max-w-4xl">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">نتائج البحث</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    @if (mb_strlen($query) >= $minLength)
                        {{ $results['total'] }} نتيجة لـ «{{ $query }}»
                    @else
                        اكتب حرفين على الأقل للبحث عبر المستأجرين والرسائل ومشتركي النشرة.
                    @endif
                </p>
            </div>

            <form method="GET" action="{{ route('admin.search') }}" class="flex w-full max-w-md gap-2">
                <input
                    type="search"
                    name="q"
                    value="{{ $query }}"
                    placeholder="بحث في المنصّة..."
                    class="w-full rounded-xl border border-mist-200 bg-white px-4 py-2.5 text-sm text-ink-700 placeholder:text-mist-400 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50"
                >
                <button
                    type="submit"
                    class="shrink-0 rounded-xl bg-emerald-500 px-4 py-2.5 text-sm font-semibold text-emerald-950 transition hover:bg-emerald-400 active:scale-[0.98]"
                >
                    بحث
                </button>
            </form>
        </div>

        @if (mb_strlen($query) >= $minLength)
            <div class="mt-6 flex flex-wrap items-center gap-2">
                <a
                    href="{{ route('admin.search', ['q' => $query, 'tab' => 'all']) }}"
                    @class([
                        'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors',
                        'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $activeTab === 'all',
                        'border-mist-200 bg-white text-mist-500 hover:text-ink-700 dark:border-ink-700 dark:bg-ink-800 dark:text-mist-400 dark:hover:text-mist-100' => $activeTab !== 'all',
                    ])
                >
                    الكل
                    <span class="rounded-full bg-mist-100 px-1.5 py-0.5 text-xs font-semibold text-mist-600 dark:bg-ink-900/60 dark:text-mist-300">{{ $results['total'] }}</span>
                </a>

                @foreach ($results['groups'] as $group)
                    <a
                        href="{{ route('admin.search', ['q' => $query, 'tab' => $group['key']]) }}"
                        @class([
                            'inline-flex items-center gap-2 rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors',
                            'border-emerald-500/30 bg-emerald-500/10 text-emerald-600 dark:text-emerald-400' => $activeTab === $group['key'],
                            'border-mist-200 bg-white text-mist-500 hover:text-ink-700 dark:border-ink-700 dark:bg-ink-800 dark:text-mist-400 dark:hover:text-mist-100' => $activeTab !== $group['key'],
                        ])
                    >
                        {{ $group['label'] }}
                        <span class="rounded-full bg-mist-100 px-1.5 py-0.5 text-xs font-semibold text-mist-600 dark:bg-ink-900/60 dark:text-mist-300">{{ count($group['items']) }}</span>
                    </a>
                @endforeach
            </div>

            <div class="mt-6 space-y-6">
                @forelse ($visibleGroups as $group)
                    <section class="overflow-hidden rounded-2xl border border-mist-200 bg-white dark:border-ink-700 dark:bg-ink-900">
                        <div class="border-b border-mist-100 px-5 py-3 dark:border-ink-700">
                            <h3 class="font-display text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $group['label'] }}</h3>
                        </div>
                        <ul class="divide-y divide-mist-100 dark:divide-ink-700">
                            @foreach ($group['items'] as $item)
                                <li>
                                    <a
                                        href="{{ $item['url'] }}"
                                        class="flex items-center justify-between gap-4 px-5 py-4 transition hover:bg-mist-50 dark:hover:bg-ink-800/80"
                                    >
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $item['title'] }}</p>
                                            <p class="mt-0.5 truncate text-xs text-mist-500 dark:text-mist-400">{{ $item['subtitle'] }}</p>
                                        </div>
                                        <span class="shrink-0 text-xs font-semibold text-emerald-600 dark:text-emerald-400">عرض</span>
                                    </a>
                                </li>
                            @endforeach
                        </ul>
                    </section>
                @empty
                    <div class="rounded-2xl border border-dashed border-mist-200 bg-white px-6 py-12 text-center dark:border-ink-700 dark:bg-ink-900">
                        <p class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">لا توجد نتائج</p>
                        <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">جرّب كلمات أخرى أو تحقق من الإملاء.</p>
                    </div>
                @endforelse
            </div>
        @endif
    </div>
@endsection
