<x-layouts.app title="سجل النشاط">
    <div
        class="space-y-6"
        x-data="{
            open: false,
            entry: null,
            openDetails(entry) {
                this.entry = entry;
                this.open = true;
            },
            closeDetails() {
                this.open = false;
                this.entry = null;
            },
        }"
        @keydown.escape.window="closeDetails()"
    >
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">سجل النشاط</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تتبع الإجراءات الحساسة داخل المؤسسة بلغة واضحة للإدارة (المالك فقط).</p>
        </div>

        <form method="GET" action="{{ route('tenant.audit-logs.index') }}" class="grid gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm sm:grid-cols-3 dark:border-ink-600 dark:bg-ink-800">
            <div>
                <label class="mb-1.5 block text-xs font-medium text-mist-500">الوحدة</label>
                <select name="module" class="w-full rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['module'] === 'all')>الكل</option>
                    @foreach ($modules as $moduleKey => $moduleLabel)
                        <option value="{{ $moduleKey }}" @selected($filters['module'] === $moduleKey)>{{ $moduleLabel }}</option>
                    @endforeach
                </select>
            </div>
            <div class="sm:col-span-2">
                <label class="mb-1.5 block text-xs font-medium text-mist-500">بحث</label>
                <div class="flex gap-2">
                    <input
                        type="search"
                        name="q"
                        value="{{ $filters['q'] }}"
                        placeholder="ابحث عن إجراء أو وحدة..."
                        class="min-w-0 flex-1 rounded-xl border border-mist-200 bg-white px-3 py-2.5 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50"
                    >
                    <button type="submit" class="rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 hover:bg-emerald-300">تصفية</button>
                </div>
            </div>
        </form>

        <div class="overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div class="w-full overflow-x-auto">
                <table class="w-full min-w-max text-start text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs uppercase tracking-wide text-mist-500 dark:border-ink-700 dark:text-mist-400">
                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">التوقيت</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">المستخدم</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-center">الإجراء</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الوحدة</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">IP</th>
                            <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">التغييرات</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($logs as $log)
                            @php
                                $view = $presented[$log->id] ?? [
                                    'summary' => $log->action,
                                    'action_label' => $log->action,
                                    'module_label' => $log->module,
                                    'badges' => [],
                                    'rows' => [],
                                ];

                                $detailEntry = [
                                    'summary' => $view['summary'],
                                    'action_label' => $view['action_label'],
                                    'module_label' => $view['module_label'],
                                    'user' => $log->user?->name ?? 'النظام',
                                    'ip' => $log->ip_address ?? '—',
                                    'time' => $log->created_at?->format('Y-m-d H:i') ?? '—',
                                    'rows' => $view['rows'],
                                ];
                            @endphp
                            <tr class="transition hover:bg-mist-50 dark:hover:bg-ink-700/40">
                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration + ($logs->currentPage() - 1) * $logs->perPage() }}</td>
                                <td class="px-4 py-3 whitespace-nowrap text-ink-900 dark:text-ink-50 text-start"><x-ui.ltr>{{ $log->created_at?->format('Y-m-d H:i') }}</x-ui.ltr></td>
                                <td class="px-4 py-3 text-ink-700 dark:text-mist-200 text-start">{{ $log->user?->name ?? 'النظام' }}</td>
                                <td class="px-4 py-3 text-center">
                                    <p class="font-medium text-ink-900 dark:text-ink-50">{{ $view['summary'] }}</p>
                                </td>
                                <td class="px-4 py-3 text-start">
                                    <span class="rounded-full bg-mist-100 px-2 py-0.5 text-xs font-semibold text-mist-600 dark:bg-ink-700 dark:text-mist-300">{{ $view['module_label'] }}</span>
                                </td>
                                <td class="px-4 py-3 font-mono text-xs text-mist-500 text-start"><x-ui.ltr>{{ $log->ip_address ?? '—' }}</x-ui.ltr></td>
                                <td class="px-4 py-3 align-middle text-start">
                                    @if ($view['rows'] !== [])
                                        <div class="flex max-w-sm flex-col gap-2">
                                            <div class="flex flex-wrap gap-1.5">
                                                @foreach (array_slice($view['badges'], 0, 2) as $badge)
                                                    <span class="inline-flex max-w-full truncate rounded-full bg-emerald-500/10 px-2 py-0.5 text-[11px] font-semibold text-emerald-800 dark:text-emerald-300" title="{{ $badge }}">
                                                        {{ $badge }}
                                                    </span>
                                                @endforeach
                                                @if (count($view['rows']) > 2)
                                                    <span class="inline-flex rounded-full bg-mist-100 px-2 py-0.5 text-[11px] font-semibold text-mist-500 dark:bg-ink-700 dark:text-mist-400">
                                                        +{{ count($view['rows']) - 2 }}
                                                    </span>
                                                @endif
                                            </div>
                                            <button
                                                type="button"
                                                @click="openDetails(@js($detailEntry))"
                                                class="inline-flex w-fit items-center gap-1 rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 active:scale-95 dark:border-ink-600 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400"
                                            >
                                                معاينة التفاصيل
                                            </button>
                                        </div>
                                    @else
                                        <span class="text-mist-400">—</span>
                                    @endif
                                </td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="7" icon="🕘" message="لا توجد سجلات نشاط بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($logs->hasPages())
                <div class="border-t border-mist-100 px-4 py-3 dark:border-ink-700">
                    {{ $logs->links() }}
                </div>
            @endif
        </div>

        {{-- Details modal: human-readable only --}}
        <div
            x-show="open"
            x-cloak
            class="fixed inset-0 z-50 flex items-center justify-center p-4"
            role="dialog"
            aria-modal="true"
            aria-labelledby="audit-details-title"
        >
            <div
                class="absolute inset-0 bg-ink-950/60 backdrop-blur-sm"
                x-show="open"
                x-transition.opacity
                @click="closeDetails()"
            ></div>

            <div
                class="relative z-10 flex max-h-[85vh] w-full max-w-2xl flex-col overflow-hidden rounded-2xl border border-mist-200 bg-white shadow-xl dark:border-ink-600 dark:bg-ink-800"
                x-show="open"
                x-transition:enter="transition ease-out duration-200"
                x-transition:enter-start="opacity-0 translate-y-2 scale-95"
                x-transition:enter-end="opacity-100 translate-y-0 scale-100"
                x-transition:leave="transition ease-in duration-150"
                x-transition:leave-start="opacity-100 translate-y-0 scale-100"
                x-transition:leave-end="opacity-0 translate-y-2 scale-95"
                @click.stop
            >
                <div class="flex items-center justify-between border-b border-mist-100 px-5 py-4 dark:border-ink-700">
                    <div>
                        <h3 id="audit-details-title" class="font-display text-base font-semibold text-ink-900 dark:text-ink-50">معاينة التفاصيل</h3>
                        <p class="mt-0.5 text-sm text-mist-500" x-text="entry?.summary || ''"></p>
                    </div>
                    <button
                        type="button"
                        @click="closeDetails()"
                        class="rounded-lg p-1.5 text-mist-400 transition hover:bg-mist-100 hover:text-mist-600 dark:hover:bg-ink-700 dark:hover:text-white"
                        aria-label="إغلاق"
                    >
                        <svg xmlns="http://www.w3.org/2000/svg" class="h-5 w-5" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.5"><path stroke-linecap="round" stroke-linejoin="round" d="M6 18 18 6M6 6l12 12" /></svg>
                    </button>
                </div>

                <div class="flex-1 space-y-5 overflow-y-auto p-5" x-show="entry">
                    <dl class="grid gap-3 text-sm sm:grid-cols-2">
                        <div class="rounded-xl border border-mist-100 px-3 py-2 dark:border-ink-700">
                            <dt class="text-xs text-mist-500">المستخدم</dt>
                            <dd class="mt-0.5 font-medium text-ink-900 dark:text-ink-50" x-text="entry?.user"></dd>
                        </div>
                        <div class="rounded-xl border border-mist-100 px-3 py-2 dark:border-ink-700">
                            <dt class="text-xs text-mist-500">التوقيت</dt>
                            <dd class="mt-0.5 font-medium text-ink-900 dark:text-ink-50" dir="ltr" x-text="entry?.time"></dd>
                        </div>
                        <div class="rounded-xl border border-mist-100 px-3 py-2 dark:border-ink-700">
                            <dt class="text-xs text-mist-500">الوحدة</dt>
                            <dd class="mt-0.5 font-medium text-ink-900 dark:text-ink-50" x-text="entry?.module_label"></dd>
                        </div>
                        <div class="rounded-xl border border-mist-100 px-3 py-2 dark:border-ink-700">
                            <dt class="text-xs text-mist-500">عنوان IP</dt>
                            <dd class="mt-0.5 font-mono text-xs text-ink-900 dark:text-ink-50" dir="ltr" x-text="entry?.ip"></dd>
                        </div>
                    </dl>

                    <div>
                        <div class="mb-2 flex items-center justify-between gap-2">
                            <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">القيم السابقة ↔ القيم الجديدة</p>
                            <span class="text-xs text-mist-400" x-text="entry?.action_label || ''"></span>
                        </div>

                        <div class="overflow-hidden rounded-xl border border-mist-200 dark:border-ink-600">
                            <table class="w-full text-sm">
                                <thead>
                                    <tr class="bg-mist-50 text-xs text-mist-500 dark:bg-ink-900/60 dark:text-mist-400">
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">الحقل</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">القيمة السابقة</th>
                                        <th class="px-4 py-3 text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400 text-start">القيمة الجديدة</th>
                                    </tr>
                                </thead>
                                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                                    <template x-for="(row, index) in (entry?.rows || [])" :key="index">
                                        <tr>
                                            <td class="px-4 py-3.5 font-medium text-ink-900 dark:text-ink-50 text-start" x-text="row.field"></td>
                                            <td class="px-4 py-3.5 text-start">
                                                <span class="inline-flex rounded-md bg-danger-solid/10 px-2 py-0.5 text-xs font-medium text-danger-solid" x-text="row.before"></span>
                                            </td>
                                            <td class="px-4 py-3.5 text-start">
                                                <span class="inline-flex rounded-md bg-emerald-500/10 px-2 py-0.5 text-xs font-medium text-emerald-700 dark:text-emerald-300" x-text="row.after"></span>
                                            </td>
                                        </tr>
                                    </template>
                                </tbody>
                            </table>
                            <p
                                class="px-3 py-6 text-center text-sm text-mist-500"
                                x-show="!(entry?.rows || []).length"
                            >
                                لا توجد تفاصيل إضافية لهذا الإجراء.
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</x-layouts.app>
