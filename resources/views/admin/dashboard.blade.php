@extends('layouts.admin')

@section('title', 'لوحة تحكم المنصّة')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">نظرة عامة</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">لوحة التحكم</span>
@endsection

@section('content')
    @php
        $total = array_sum(array_column($distribution, 'count'));
        $acc = 0;
        $segments = [];
        foreach ($distribution as $d) {
            if ($d['count'] <= 0) {
                continue;
            }
            $start = $total > 0 ? ($acc / $total) * 360 : 0;
            $acc += $d['count'];
            $end = $total > 0 ? ($acc / $total) * 360 : 0;
            $segments[] = "{$d['color']} {$start}deg {$end}deg";
        }
        $gradient = $segments === []
            ? 'conic-gradient(#e5e7eb 0deg 360deg)'
            : 'conic-gradient(' . implode(', ', $segments) . ')';

        $activityIcons = [
            'approval' => '<path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" />',
            'signup' => '<path stroke-linecap="round" stroke-linejoin="round" d="M19 7.5v3m0 0v3m0-3h3m-3 0h-3m-2.25-4.125a3.375 3.375 0 1 1-6.75 0 3.375 3.375 0 0 1 6.75 0ZM4 19.235v-.11a6.375 6.375 0 0 1 12.75 0v.109A12.318 12.318 0 0 1 10.374 21c-2.331 0-4.512-.645-6.374-1.765Z" />',
            'suspension' => '<path stroke-linecap="round" stroke-linejoin="round" d="M18.364 18.364A9 9 0 0 0 5.636 5.636m12.728 12.728A9 9 0 0 1 5.636 5.636m12.728 12.728L5.636 5.636" />',
            'security' => '<path stroke-linecap="round" stroke-linejoin="round" d="M12 9v3.75m-9.303 3.376c-.866 1.5.217 3.374 1.948 3.374h14.71c1.73 0 2.813-1.874 1.948-3.374L13.949 3.378c-.866-1.5-3.032-1.5-3.898 0L2.697 16.126ZM12 15.75h.007v.008H12v-.008Z" />',
        ];
        $activityTone = [
            'approval' => 'bg-emerald-400/15 text-emerald-500 dark:text-emerald-400',
            'signup' => 'bg-sky-500/15 text-sky-500',
            'suspension' => 'bg-danger-solid/15 text-danger-solid',
            'security' => 'bg-amber-500/15 text-amber-500',
        ];
        $planMax = max(1, ...array_column($planBreakdown, 'count') ?: [1]);
    @endphp

    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">مرحبًا، مشرف المنصّة</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">نظرة حيّة على صحّة المنصّة والإجراءات المطلوبة اليوم.</p>
        </div>

        <div class="inline-flex rounded-xl border border-mist-200 bg-white p-1 text-sm dark:border-ink-600 dark:bg-ink-800">
            @foreach (['today' => 'اليوم', '7d' => '7 أيام', '30d' => '30 يومًا'] as $key => $label)
                <a
                    href="{{ route('admin.dashboard', ['range' => $key]) }}"
                    @class([
                        'rounded-lg px-3 py-1.5 font-medium transition duration-200',
                        'bg-emerald-400 text-emerald-900 shadow-sm' => $range === $key,
                        'text-mist-500 hover:text-ink-700 dark:hover:text-mist-200' => $range !== $key,
                    ])
                >{{ $label }}</a>
            @endforeach
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3 xl:grid-cols-5">
        <x-admin.stat-card
            :label="$metrics['total']['label']"
            :value="$metrics['total']['value']"
            :delta="$metrics['total']['delta']"
            :trend="$metrics['total']['trend']"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3.75 21h16.5M4.5 3h15M5.25 3v18m13.5-18v18M9 6.75h6M9 10.5h6M9 14.25h6" /></svg>'
        />
        <x-admin.stat-card
            :label="$metrics['active']['label']"
            :value="$metrics['active']['value']"
            :delta="$metrics['active']['delta']"
            :trend="$metrics['active']['trend']"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M9 12.75 11.25 15 15 9.75M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>'
        />
        <x-admin.stat-card
            :label="$metrics['pending']['label']"
            :value="$metrics['pending']['value']"
            :delta="$metrics['pending']['delta']"
            :trend="$metrics['pending']['trend']"
            :accent="true"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v6h4.5m4.5 0a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>'
        />
        <x-admin.stat-card
            :label="$metrics['suspended']['label']"
            :value="$metrics['suspended']['value']"
            :delta="$metrics['suspended']['delta']"
            :trend="$metrics['suspended']['trend']"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M15.75 5.25v13.5m-7.5-13.5v13.5" /></svg>'
        />
        <x-admin.stat-card
            :label="$metrics['mrr']['label']"
            :value="$metrics['mrr']['value']"
            icon='<svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M12 6v12m-3-2.818.879.659c1.171.879 3.07.879 4.242 0 1.172-.879 1.172-2.303 0-3.182C13.536 12.219 12.768 12 12 12c-.725 0-1.45-.22-2.003-.659-1.106-.879-1.106-2.303 0-3.182s2.9-.879 4.006 0l.415.33M21 12a9 9 0 1 1-18 0 9 9 0 0 1 18 0Z" /></svg>'
        />
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-mist-200 bg-white shadow-sm lg:col-span-2 dark:border-ink-600 dark:bg-ink-800">
            <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                <div class="flex items-center gap-2">
                    <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">بانتظار موافقتك</h3>
                    <span class="rounded-full bg-amber-500/15 px-2 py-0.5 text-xs font-bold text-amber-600 dark:text-amber-400">{{ count($approvalQueue) }}</span>
                </div>
                <a href="{{ route('admin.tenants', ['status' => 'pending_approval']) }}" class="text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400">عرض الكل</a>
            </div>

            <ul class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($approvalQueue as $item)
                    <li class="flex flex-col gap-3 px-5 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <div class="flex items-center gap-3">
                            <span class="flex h-10 w-10 shrink-0 items-center justify-center rounded-xl bg-mist-100 font-display text-sm font-bold text-mist-500 dark:bg-ink-700 dark:text-mist-300">
                                {{ mb_substr($item['company'], 0, 1) }}
                            </span>
                            <div class="min-w-0">
                                <a href="{{ route('admin.tenants.show', $item['slug']) }}" class="truncate text-sm font-semibold text-ink-900 hover:text-emerald-600 dark:text-ink-50 dark:hover:text-emerald-400">{{ $item['company'] }}</a>
                                <p class="truncate text-xs text-mist-500 dark:text-mist-400">{{ $item['owner'] }} · {{ $item['email'] }}</p>
                            </div>
                        </div>

                        <div class="flex items-center gap-2 ps-13 sm:ps-0">
                            <span class="rounded-md bg-mist-100 px-2 py-0.5 text-xs font-medium text-mist-600 dark:bg-ink-700 dark:text-mist-300">{{ $item['plan'] }}</span>
                            <span class="hidden text-xs text-mist-400 sm:inline dark:text-mist-500">{{ $item['waiting'] }}</span>
                            <a href="{{ route('admin.tenants.show', $item['slug']) }}" class="rounded-lg bg-emerald-400 px-3 py-1.5 text-xs font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">مراجعة</a>
                        </div>
                    </li>
                @empty
                    <li class="px-5 py-10 text-center text-sm text-mist-500 dark:text-mist-400">لا توجد طلبات بانتظار الموافقة</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">توزيع حالات المستأجرين</h3>

            <div class="mt-4 flex items-center justify-center">
                <div class="relative h-40 w-40 rounded-full" style="background: {{ $gradient }};">
                    <div class="absolute inset-[14px] flex flex-col items-center justify-center rounded-full bg-white dark:bg-ink-800">
                        <span class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">{{ $total }}</span>
                        <span class="text-xs text-mist-500 dark:text-mist-400">مستأجر</span>
                    </div>
                </div>
            </div>

            <ul class="mt-5 space-y-2">
                @foreach ($distribution as $d)
                    <li class="flex items-center justify-between text-sm">
                        <span class="flex items-center gap-2 text-mist-600 dark:text-mist-300">
                            <span class="h-2.5 w-2.5 rounded-full" style="background: {{ $d['color'] }};"></span>
                            {{ $d['label'] }}
                        </span>
                        <span class="font-medium text-ink-900 dark:text-ink-50">{{ $d['count'] }}</span>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-6 grid grid-cols-1 gap-4 lg:grid-cols-3">
        <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">توزيع الخطط</h3>
            <ul class="mt-4 space-y-4">
                @forelse ($planBreakdown as $plan)
                    <li>
                        <div class="flex items-center justify-between text-sm">
                            <span class="font-medium text-ink-700 dark:text-mist-200">{{ $plan['name'] }}</span>
                            <span class="text-mist-500">{{ $plan['count'] }} · {{ $plan['percent'] }}%</span>
                        </div>
                        <div class="mt-2 h-2 overflow-hidden rounded-full bg-mist-100 dark:bg-ink-700">
                            <div class="h-full rounded-full bg-emerald-400 transition-all" style="width: {{ ($plan['count'] / $planMax) * 100 }}%"></div>
                        </div>
                    </li>
                @empty
                    <li class="py-6 text-center text-sm text-mist-500">لا توجد بيانات خطط بعد</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">أحدث التسجيلات</h3>
                <a href="{{ route('admin.tenants', ['status' => 'all']) }}" class="text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400">الكل</a>
            </div>
            <ul class="divide-y divide-mist-100 dark:divide-ink-700">
                @forelse ($recentSignups as $signup)
                    <li class="flex items-center justify-between gap-3 px-5 py-3">
                        <div class="min-w-0">
                            <a href="{{ route('admin.tenants.show', $signup['slug']) }}" class="truncate text-sm font-semibold text-ink-900 hover:text-emerald-600 dark:text-ink-50">{{ $signup['name'] }}</a>
                            <p class="text-xs text-mist-500">{{ $signup['plan'] }} · {{ $signup['created'] }}</p>
                        </div>
                        <x-admin.status-badge :status="$signup['status']" />
                    </li>
                @empty
                    <li class="px-5 py-8 text-center text-sm text-mist-500">لا توجد تسجيلات بعد</li>
                @endforelse
            </ul>
        </div>

        <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">حالة النظام</h3>
            <ul class="mt-4 space-y-3">
                @foreach ($systemStatus as $item)
                    <li class="flex items-start justify-between gap-3 rounded-xl border border-mist-100 px-3 py-2.5 dark:border-ink-700">
                        <div>
                            <p class="text-sm font-medium text-ink-700 dark:text-mist-200">{{ $item['label'] }}</p>
                            @if ($item['hint'])
                                <p class="text-xs text-mist-400">{{ $item['hint'] }}</p>
                            @endif
                        </div>
                        <div class="flex items-center gap-2">
                            <span class="font-display text-sm font-bold text-ink-900 dark:text-ink-50">{{ $item['value'] }}</span>
                            <span @class([
                                'h-2.5 w-2.5 rounded-full',
                                'bg-emerald-400' => $item['ok'],
                                'bg-amber-500' => ! $item['ok'],
                            ])></span>
                        </div>
                    </li>
                @endforeach
            </ul>
        </div>
    </div>

    <div class="mt-6 rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
        <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
            <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">آخر نشاطات المنصّة</h3>
            <a href="{{ route('admin.audit-log') }}" class="text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400">سجل النشاط</a>
        </div>

        <ul class="divide-y divide-mist-100 dark:divide-ink-700">
            @forelse ($activity as $event)
                <li class="flex items-center gap-3 px-5 py-3.5">
                    <span class="flex h-9 w-9 shrink-0 items-center justify-center rounded-xl {{ $activityTone[$event['type']] ?? $activityTone['approval'] }}">
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-4.5 w-4.5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5">
                            {!! $activityIcons[$event['type']] ?? $activityIcons['approval'] !!}
                        </svg>
                    </span>
                    <div class="min-w-0 flex-1">
                        <p class="text-sm text-ink-700 dark:text-mist-200">
                            <span class="font-semibold text-ink-900 dark:text-ink-50">{{ $event['actor'] }}</span>
                            {{ $event['action'] }}
                            <span class="font-medium text-emerald-600 dark:text-emerald-400">{{ $event['target'] }}</span>
                        </p>
                    </div>
                    <span class="shrink-0 text-xs text-mist-400 dark:text-mist-500">{{ $event['time'] }}</span>
                </li>
            @empty
                <li class="px-5 py-10 text-center text-sm text-mist-500 dark:text-mist-400">لا يوجد نشاط مسجّل بعد</li>
            @endforelse
        </ul>
    </div>
@endsection
