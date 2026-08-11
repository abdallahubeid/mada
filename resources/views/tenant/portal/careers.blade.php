@extends('tenant.portal.layout')

@section('title', ($portalSettings->careers_title ?: 'الوظائف').' — '.$company['name'])

@section('content')
    <section class="relative overflow-hidden border-b border-mist-200 dark:border-ink-700">
        <div class="absolute inset-0 portal-grid-bg"></div>
        <div class="relative mx-auto max-w-6xl px-4 py-12 sm:px-6 sm:py-16">
            <p class="text-xs font-bold tracking-wide text-emerald-600 uppercase dark:text-emerald-400">{{ $portalSettings->careers_badge_text ?: 'الوظائف' }}</p>
            <h1 class="mt-2 font-display text-3xl font-bold text-ink-900 sm:text-4xl dark:text-ink-50">{{ $portalSettings->careers_title ?: 'الشواغر المتاحة' }}</h1>
            <p class="mt-3 max-w-2xl text-sm text-mist-500 dark:text-mist-400">{{ $portalSettings->careers_subtitle ?: 'ابحث عن الدور المناسب حسب القسم أو المسمى الوظيفي، وقدّم في دقائق.' }}</p>

            <form method="GET" action="{{ route('portal.careers', $slug) }}" class="mt-8 grid gap-3 rounded-2xl border border-mist-200/80 bg-white/80 p-4 shadow-lg backdrop-blur-xl sm:grid-cols-[1fr_220px_auto] dark:border-ink-600/70 dark:bg-ink-900/60">
                <input
                    type="search"
                    name="q"
                    value="{{ $filters['q'] ?? '' }}"
                    placeholder="ابحث عن مسمى وظيفي..."
                    class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50"
                >
                <select
                    name="department"
                    class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50"
                >
                    <option value="all" @selected(($filters['department'] ?? 'all') === 'all')>كل الأقسام</option>
                    @foreach ($departments as $department)
                        <option value="{{ $department }}" @selected(($filters['department'] ?? '') === $department)>{{ $department }}</option>
                    @endforeach
                </select>
                <button type="submit" class="rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-bold text-emerald-950 shadow-[0_0_24px_rgba(78,222,163,0.3)] transition hover:bg-emerald-300">
                    تصفية النتائج
                </button>
            </form>
        </div>
    </section>

    <section class="mx-auto max-w-6xl px-4 py-10 sm:px-6">
        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-3">
            @forelse ($jobs as $job)
                <article class="group flex flex-col rounded-2xl border border-mist-200 bg-white p-5 shadow-sm veyra-card hover:border-emerald-400/50 hover:shadow-[0_16px_40px_rgba(15,23,42,0.08)] dark:border-ink-600 dark:bg-ink-800">
                    <div class="flex items-start justify-between gap-3">
                        <span class="rounded-full bg-emerald-400/10 px-2.5 py-0.5 text-[11px] font-bold text-emerald-700 dark:text-emerald-300">{{ $job['department'] }}</span>
                        <span class="text-[11px] text-mist-400">{{ $job['posted_at'] }}</span>
                    </div>
                    <h2 class="mt-3 font-display text-lg font-semibold text-ink-900 dark:text-ink-50">{{ $job['title'] }}</h2>
                    <p class="mt-2 flex-1 text-sm text-mist-500 dark:text-mist-400">{{ $job['summary'] }}</p>
                    <p class="mt-3 text-xs text-mist-400">{{ $job['location'] }} · {{ $job['type'] }}</p>
                    <div class="mt-4 flex gap-2">
                        <a href="{{ route('portal.jobs.show', [$slug, $job['slug']]) }}" class="inline-flex flex-1 items-center justify-center rounded-xl bg-emerald-400 px-4 py-2.5 text-sm font-bold text-emerald-950 transition hover:bg-emerald-300">
                            تقديم سريع
                        </a>
                        <a href="{{ route('portal.jobs.show', [$slug, $job['slug']]) }}" class="inline-flex items-center justify-center rounded-xl border border-mist-200 px-3 py-2.5 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 dark:border-ink-600 dark:text-mist-200">
                            التفاصيل
                        </a>
                    </div>
                </article>
            @empty
                <div class="md:col-span-2 xl:col-span-3 rounded-2xl border border-dashed border-mist-300 bg-white px-6 py-14 text-center dark:border-ink-600 dark:bg-ink-800">
                    <p class="font-medium text-ink-700 dark:text-mist-200">لا توجد وظائف مطابقة لبحثك.</p>
                    <a href="{{ route('portal.careers', $slug) }}" class="mt-3 inline-block text-sm font-bold text-emerald-600 dark:text-emerald-400">إعادة ضبط التصفية</a>
                </div>
            @endforelse
        </div>
    </section>
@endsection
