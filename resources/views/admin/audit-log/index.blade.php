@extends('layouts.admin')

@section('title', 'سجل النشاط والرقابة')

@section('breadcrumbs')
    <span class="text-mist-500 dark:text-mist-400">المنصّة</span>
    <span class="mx-1.5 text-mist-300 dark:text-mist-600">/</span>
    <span class="text-ink-700 dark:text-mist-200">سجل النشاط</span>
@endsection

@section('content')
    @php
        $actionMeta = [
            'approval' => ['label' => 'موافقة', 'badge' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400', 'dot' => 'bg-emerald-400'],
            'suspension' => ['label' => 'إيقاف', 'badge' => 'bg-danger-solid/10 text-danger-solid', 'dot' => 'bg-danger-solid'],
            'role_change' => ['label' => 'تغيير صلاحية', 'badge' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400', 'dot' => 'bg-sky-500'],
            'impersonation' => ['label' => 'انتحال شخصية', 'badge' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400', 'dot' => 'bg-amber-500'],
            'settings_change' => ['label' => 'تغيير إعدادات', 'badge' => 'bg-violet-500/10 text-violet-600 dark:text-violet-400', 'dot' => 'bg-violet-500'],
            'security_flag' => ['label' => 'تنبيه أمني', 'badge' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400', 'dot' => 'bg-amber-500'],
        ];
        $selectClass = 'rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm text-ink-700 focus:border-emerald-400 focus:outline-none focus:ring-2 focus:ring-emerald-400/30 dark:border-ink-600 dark:bg-ink-800 dark:text-ink-50';
    @endphp

    <div x-data="{ open: false, entry: null }">
        {{-- Header --}}
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">سجل النشاط والرقابة</h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">سجل غير قابل للتعديل لكل إجراء حسّاس على المنصّة (NFR-05).</p>
            </div>

            <button type="button" disabled title="يتوفّر في المرحلة 4" class="inline-flex cursor-not-allowed items-center gap-2 rounded-xl border border-mist-200 px-4 py-2 text-sm font-medium text-mist-400 dark:border-ink-600 dark:text-mist-500">
                <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M3 16.5v2.25A2.25 2.25 0 0 0 5.25 21h13.5A2.25 2.25 0 0 0 21 18.75V16.5M16.5 12 12 16.5m0 0L7.5 12m4.5 4.5V3" /></svg>
                تصدير
                <span class="rounded-full bg-mist-100 px-1.5 py-0.5 text-[10px] font-semibold uppercase tracking-wide text-mist-500 dark:bg-ink-700 dark:text-mist-400">Phase 4</span>
            </button>
        </div>

        {{-- Filter bar --}}
        <form method="GET" class="mt-6 grid grid-cols-1 gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-2 lg:grid-cols-4 dark:border-ink-600 dark:bg-ink-800">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-mist-500 dark:text-mist-400">المستأجر / الكيان</label>
                <select name="tenant" class="{{ $selectClass }} w-full">
                    <option value="">الكل</option>
                    @foreach ($tenants as $t)
                        <option value="{{ $t }}" @selected($filters['tenant'] === $t)>{{ $t }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-mist-500 dark:text-mist-400">نوع الإجراء</label>
                <select name="action" class="{{ $selectClass }} w-full">
                    <option value="">الكل</option>
                    @foreach ($actionTypes as $key => $label)
                        <option value="{{ $key }}" @selected($filters['action'] === $key)>{{ $label }}</option>
                    @endforeach
                </select>
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-mist-500 dark:text-mist-400">النطاق الزمني</label>
                <input type="date" name="from" class="{{ $selectClass }} w-full">
            </div>
            <div>
                <label class="mb-1.5 block text-xs font-medium text-mist-500 dark:text-mist-400">بحث عن منفّذ</label>
                <div class="flex gap-2">
                    <input type="search" name="actor" value="{{ $filters['actor'] }}" placeholder="اسم المنفّذ..." class="{{ $selectClass }} min-w-0 flex-1">
                    <button type="submit" class="shrink-0 rounded-xl bg-emerald-400 px-3 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition duration-200 hover:bg-emerald-300 active:scale-95">تصفية</button>
                </div>
            </div>
        </form>

        {{-- Audit table --}}
        <div class="mt-4 overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-max text-start text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs uppercase tracking-wide text-mist-500 dark:border-ink-700 dark:text-mist-400">
                            <th class="px-5 py-3 text-start font-semibold">التوقيت</th>
                            <th class="px-5 py-3 text-start font-semibold">المنفّذ</th>
                            <th class="px-5 py-3 text-start font-semibold">نوع الإجراء</th>
                            <th class="px-5 py-3 text-start font-semibold">الهدف</th>
                            <th class="px-5 py-3 text-start font-semibold">عنوان IP</th>
                            <th class="px-5 py-3 text-end font-semibold">التفاصيل</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($entries as $entry)
                            <tr class="transition duration-150 hover:bg-mist-50 dark:hover:bg-ink-700/40">
                                <td class="px-5 py-3.5">
                                    <p class="font-medium text-ink-900 dark:text-ink-50">{{ $entry['time_abs'] }}</p>
                                    <p class="text-xs text-mist-400 dark:text-mist-500">{{ $entry['time_rel'] }}</p>
                                </td>
                                <td class="px-5 py-3.5">
                                    <div class="flex items-center gap-2">
                                        <span class="flex h-7 w-7 shrink-0 items-center justify-center rounded-full text-xs font-bold {{ $entry['actor_type'] === 'system' ? 'bg-sky-500/15 text-sky-500' : 'bg-emerald-400/15 text-emerald-600 dark:text-emerald-400' }}">
                                            {{ $entry['actor_type'] === 'system' ? 'S' : mb_substr($entry['actor'], 0, 1) }}
                                        </span>
                                        <span class="text-ink-700 dark:text-mist-200">{{ $entry['actor'] }}</span>
                                    </div>
                                </td>
                                <td class="px-5 py-3.5">
                                    <span class="inline-flex items-center gap-1.5 rounded-full px-2.5 py-1 text-xs font-medium {{ $actionMeta[$entry['action']]['badge'] }}">
                                        <span class="h-1.5 w-1.5 rounded-full {{ $actionMeta[$entry['action']]['dot'] }}"></span>
                                        {{ $actionMeta[$entry['action']]['label'] }}
                                    </span>
                                </td>
                                <td class="px-5 py-3.5 text-ink-700 dark:text-mist-200">{{ $entry['target'] }}</td>
                                <td class="px-5 py-3.5 font-mono text-xs text-mist-500 dark:text-mist-400">{{ $entry['ip'] }}</td>
                                <td class="px-5 py-3.5 text-end">
                                    <button type="button" @click="entry = @js($entry); open = true" class="inline-flex items-center gap-1 rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-ink-700 transition duration-200 hover:border-emerald-400 hover:text-emerald-600 active:scale-95 dark:border-ink-600 dark:text-mist-200 dark:hover:text-emerald-400">
                                        عرض
                                    </button>
                                </td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="6" class="px-5 py-16 text-center">
                                    <p class="text-sm font-medium text-ink-900 dark:text-ink-50">لا توجد سجلات مطابقة</p>
                                    <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">جرّب تعديل معايير التصفية.</p>
                                </td>
                            </tr>
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        {{-- Detail slide-over drawer --}}
        <div
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="opacity-0"
            x-transition:enter-end="opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="opacity-100"
            x-transition:leave-end="opacity-0"
            @click="open = false"
            class="fixed inset-0 z-50 bg-ink-950/60 backdrop-blur-sm"
        ></div>

        <aside
            x-show="open"
            x-cloak
            x-transition:enter="transition ease-out duration-300"
            x-transition:enter-start="translate-x-full rtl:-translate-x-full opacity-0"
            x-transition:enter-end="translate-x-0 opacity-100"
            x-transition:leave="transition ease-in duration-200"
            x-transition:leave-start="translate-x-0 opacity-100"
            x-transition:leave-end="translate-x-full rtl:-translate-x-full opacity-0"
            class="fixed inset-y-0 end-0 z-50 flex w-full max-w-md flex-col border-s border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800"
        >
            <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                <h3 class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">تفاصيل السجل</h3>
                <button type="button" @click="open = false" class="rounded-lg p-1 text-mist-400 transition hover:bg-mist-100 hover:text-mist-600 active:scale-90 dark:hover:bg-ink-700 dark:hover:text-white" aria-label="إغلاق">
                    <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                </button>
            </div>

            <div class="flex-1 space-y-5 overflow-y-auto p-5" x-show="entry">
                <dl class="space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-mist-500 dark:text-mist-400">المنفّذ</dt>
                        <dd class="font-medium text-ink-900 dark:text-ink-50" x-text="entry?.actor"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-mist-500 dark:text-mist-400">الهدف</dt>
                        <dd class="font-medium text-ink-900 dark:text-ink-50" x-text="entry?.target"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-mist-500 dark:text-mist-400">التوقيت</dt>
                        <dd class="font-medium text-ink-900 dark:text-ink-50" x-text="entry?.time_abs"></dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-mist-500 dark:text-mist-400">عنوان IP</dt>
                        <dd class="font-mono text-xs text-ink-900 dark:text-ink-50" x-text="entry?.ip"></dd>
                    </div>
                </dl>

                {{-- Field-level diff --}}
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-mist-400">التغييرات</p>
                    <div class="space-y-2">
                        <template x-for="(change, i) in (entry?.changes || [])" :key="i">
                            <div class="rounded-xl border border-mist-200 p-3 dark:border-ink-600">
                                <p class="font-mono text-xs text-mist-500 dark:text-mist-400" x-text="change.field"></p>
                                <div class="mt-1.5 flex items-center gap-2 text-sm">
                                    <span class="rounded-md bg-danger-solid/10 px-2 py-0.5 font-mono text-xs text-danger-solid line-through" x-text="change.from"></span>
                                    <svg xmlns="http://www.w3.org/2000/svg" class="h-4 w-4 shrink-0 text-mist-400 rtl:-scale-x-100" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M13.5 4.5 21 12m0 0-7.5 7.5M21 12H3" /></svg>
                                    <span class="rounded-md bg-emerald-500/10 px-2 py-0.5 font-mono text-xs text-emerald-600 dark:text-emerald-400" x-text="change.to"></span>
                                </div>
                            </div>
                        </template>
                    </div>
                </div>

                {{-- Raw JSON payload --}}
                <div>
                    <p class="mb-2 text-xs font-semibold uppercase tracking-wide text-mist-500 dark:text-mist-400">الحمولة الكاملة (JSON)</p>
                    <pre class="overflow-x-auto rounded-xl bg-ink-950 p-4 text-xs leading-relaxed text-mist-200" dir="ltr"><code x-text="JSON.stringify(entry, null, 2)"></code></pre>
                </div>
            </div>
        </aside>
    </div>
@endsection
