@php
    $user = auth()->user();
    $tenant = $user?->tenant;
    $statusLabel = $tenant?->status?->label() ?? '—';
@endphp

<x-layouts.app title="لوحة التحكم">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">
                    مرحباً بعودتك، {{ $user?->name }}
                </h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    <span class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant?->name }}</span>
                    <span class="mx-1.5 text-mist-300 dark:text-mist-600">·</span>
                    {{ $statusLabel }}
                </p>
            </div>
            @can('tenant.reports.view')
                <a
                    href="{{ route('tenant.reports.index') }}"
                    class="inline-flex items-center justify-center rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600 dark:text-mist-200 dark:hover:border-emerald-400 dark:hover:text-emerald-400"
                >
                    التقارير والتصدير
                </a>
            @endcan
        </div>

        {{-- KPI cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">إجمالي الموظفين</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" data-testid="kpi-total-employees">{{ $kpis['total_employees'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">العقود النشطة</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" data-testid="kpi-active-contracts">{{ $kpis['active_contracts'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">طلبات إجازة معلّقة</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" data-testid="kpi-pending-leaves">{{ $kpis['pending_leaves'] }}</p>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">نسبة الحضور الشهرية</p>
                <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" data-testid="kpi-attendance-rate">{{ $kpis['attendance_rate'] }}%</p>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid gap-4 xl:grid-cols-3">
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800 xl:col-span-2">
                <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">الحضور مقابل الغياب (شهري)</h3>
                <div class="mt-4 h-72">
                    <canvas id="attendanceChart" aria-label="Attendance chart"></canvas>
                </div>
            </div>
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">توزيع الموظفين حسب القسم</h3>
                <div class="mt-4 h-72">
                    <canvas id="departmentChart" aria-label="Department chart"></canvas>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">التوظيف والاستقالات</h3>
                <dl class="mt-4 space-y-3 text-sm">
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-mist-500 dark:text-mist-400">وظائف مفتوحة</dt>
                        <dd class="font-semibold text-ink-900 dark:text-ink-50" data-testid="pipeline-open-jobs">{{ $pipeline['open_jobs'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-mist-500 dark:text-mist-400">طلبات التقديم</dt>
                        <dd class="font-semibold text-ink-900 dark:text-ink-50" data-testid="pipeline-applications">{{ $pipeline['applications'] }}</dd>
                    </div>
                    <div class="flex items-center justify-between gap-3">
                        <dt class="text-mist-500 dark:text-mist-400">استقالات (90 يوم)</dt>
                        <dd class="font-semibold text-ink-900 dark:text-ink-50" data-testid="pipeline-resigned">{{ $pipeline['resigned_90d'] }}</dd>
                    </div>
                </dl>
            </div>

            {{-- Quick approvals --}}
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-display text-lg font-semibold text-ink-900 dark:text-ink-50">إجراءات سريعة للاعتماد</h3>
                    @can('hr.leaves.view_any')
                        <a href="{{ route('hr.leaves.index') }}" class="text-sm font-medium text-emerald-600 hover:underline dark:text-emerald-400">كل الطلبات</a>
                    @endcan
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">طلبات إجازة معلّقة</p>
                        <ul class="mt-2 space-y-2">
                            @forelse ($pendingLeaves as $leave)
                                <li class="rounded-xl border border-mist-100 px-3 py-2 dark:border-ink-700">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-ink-900 dark:text-ink-50">{{ $leave->employee?->full_name }}</p>
                                            <p class="text-xs text-mist-500">
                                                {{ $leave->leaveType?->name }} · {{ $leave->days_count }} يوم
                                                @if ($leave->requires_manager_escalation)
                                                    · تصعيد {{ $leave->current_approval_level }}/{{ $leave->approval_level }}
                                                @endif
                                            </p>
                                        </div>
                                        @can('hr.leaves.approve')
                                            <form method="POST" action="{{ route('hr.leaves.approve', $leave) }}">
                                                @csrf
                                                <button type="submit" class="rounded-lg bg-emerald-400 px-2.5 py-1 text-xs font-semibold text-emerald-900 hover:bg-emerald-300">
                                                    اعتماد
                                                </button>
                                            </form>
                                        @endcan
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-mist-500">لا توجد طلبات معلّقة.</li>
                            @endforelse
                        </ul>
                    </div>

                    <div>
                        <p class="text-xs font-semibold uppercase tracking-wide text-mist-500">عقود تنتهي خلال 30 يوماً</p>
                        <ul class="mt-2 space-y-2">
                            @forelse ($expiringContracts as $contract)
                                <li class="rounded-xl border border-mist-100 px-3 py-2 dark:border-ink-700">
                                    <div class="flex items-start justify-between gap-2">
                                        <div class="min-w-0">
                                            <p class="truncate text-sm font-medium text-ink-900 dark:text-ink-50">{{ $contract->employee?->full_name }}</p>
                                            <p class="text-xs text-mist-500" dir="ltr">ينتهي {{ $contract->end_date?->format('Y-m-d') }}</p>
                                        </div>
                                        @can('hr.contracts.update')
                                            <a
                                                href="{{ route('hr.contracts.edit', $contract) }}"
                                                class="rounded-lg border border-mist-200 px-2.5 py-1 text-xs font-semibold text-ink-700 hover:border-emerald-400 dark:border-ink-600 dark:text-mist-200"
                                            >
                                                تجديد
                                            </a>
                                        @endcan
                                    </div>
                                </li>
                            @empty
                                <li class="text-sm text-mist-500">لا توجد عقود قريبة الانتهاء.</li>
                            @endforelse
                        </ul>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @push('scripts')
        <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
        <script>
            (() => {
                const attendance = @json($attendanceChart);
                const departments = @json($departmentChart);
                const textColor = document.documentElement.classList.contains('dark') ? '#E5E7EB' : '#334155';
                const gridColor = document.documentElement.classList.contains('dark') ? 'rgba(148,163,184,0.15)' : 'rgba(148,163,184,0.25)';

                const attendanceEl = document.getElementById('attendanceChart');
                if (attendanceEl && window.Chart) {
                    new Chart(attendanceEl, {
                        type: 'line',
                        data: {
                            labels: attendance.labels,
                            datasets: [
                                {
                                    label: 'حضور',
                                    data: attendance.present,
                                    borderColor: '#34d399',
                                    backgroundColor: 'rgba(52, 211, 153, 0.15)',
                                    tension: 0.35,
                                    fill: true,
                                },
                                {
                                    label: 'غياب',
                                    data: attendance.absent,
                                    borderColor: '#f87171',
                                    backgroundColor: 'rgba(248, 113, 113, 0.12)',
                                    tension: 0.35,
                                    fill: true,
                                },
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { labels: { color: textColor } } },
                            scales: {
                                x: { ticks: { color: textColor }, grid: { color: gridColor } },
                                y: { beginAtZero: true, ticks: { color: textColor, precision: 0 }, grid: { color: gridColor } },
                            },
                        },
                    });
                }

                const departmentEl = document.getElementById('departmentChart');
                if (departmentEl && window.Chart) {
                    new Chart(departmentEl, {
                        type: 'doughnut',
                        data: {
                            labels: departments.labels.length ? departments.labels : ['بدون أقسام'],
                            datasets: [{
                                data: departments.values.length ? departments.values : [1],
                                backgroundColor: ['#34d399', '#38bdf8', '#fbbf24', '#a78bfa', '#f472b6', '#94a3b8', '#2dd4bf', '#fb7185'],
                            }],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { position: 'bottom', labels: { color: textColor } } },
                        },
                    });
                }
            })();
        </script>
    @endpush
</x-layouts.app>
