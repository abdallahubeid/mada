@php
    $user = auth()->user();
    $tenant = $user?->tenant;
    $statusLabel = $tenant?->status?->label() ?? '—';
@endphp

<x-layouts.app title="لوحة التحكم">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">
                    مرحباً بعودتك، {{ $user?->name }}
                </h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    <span class="font-medium text-ink-700 dark:text-mist-200">{{ $tenant?->name }}</span>
                    <span class="mx-1.5 text-mist-400" aria-hidden="true">·</span>
                    {{ $statusLabel }}
                </p>
            </div>
            @can('tenant.reports.view')
                <a
                    href="{{ route('tenant.reports.index') }}"
                    class="ui-btn ui-btn-secondary"
                >
                    التقارير والتصدير
                </a>
            @endcan
        </div>

        {{-- KPI cards --}}
        <div class="grid grid-cols-2 gap-3 sm:gap-4 xl:grid-cols-4">
            <div class="ui-card p-4">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">إجمالي الموظفين</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-total-employees">{{ $kpis['total_employees'] }}</p>
            </div>
            <div class="ui-card p-4">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">العقود النشطة</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-active-contracts">{{ $kpis['active_contracts'] }}</p>
            </div>
            <div class="ui-card p-4">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">طلبات إجازة معلّقة</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-pending-leaves">{{ $kpis['pending_leaves'] }}</p>
            </div>
            <div class="ui-card p-4">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">نسبة الحضور الشهرية</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-attendance-rate">{{ $kpis['attendance_rate'] }}%</p>
            </div>
        </div>

        {{-- Charts --}}
        <div class="grid gap-4 xl:grid-cols-3">
            <div class="ui-card p-4 xl:col-span-2">
                <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">الحضور مقابل الغياب (شهري)</h3>
                <div class="mt-4 h-72">
                    <canvas id="attendanceChart" role="img" aria-label="مخطط خطي لعدد أيام الحضور والغياب في كل شهر"></canvas>
                </div>
            </div>
            <div class="ui-card p-4">
                <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">توزيع الموظفين حسب القسم</h3>
                <div class="mt-4 h-72">
                    <canvas id="departmentChart" role="img" aria-label="مخطط أعمدة لعدد الموظفين في كل قسم، من الأكبر إلى الأصغر"></canvas>
                </div>
            </div>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            <div class="ui-card p-4">
                <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">التوظيف والاستقالات</h3>
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
            <div class="ui-card p-4 lg:col-span-2">
                <div class="flex items-center justify-between gap-3">
                    <h3 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">إجراءات سريعة للاعتماد</h3>
                    @can('hr.leaves.view_any')
                        <a href="{{ route('hr.leaves.index') }}" class="text-sm font-medium text-brand-600 hover:underline dark:text-brand-300">كل الطلبات</a>
                    @endcan
                </div>

                <div class="mt-4 grid gap-4 md:grid-cols-2">
                    <div>
                        <p class="text-xs font-semibold uppercase text-mist-500">طلبات إجازة معلّقة</p>
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
                                                <button type="submit" class="ui-btn ui-btn-primary ui-btn-sm">
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
                        <p class="text-xs font-semibold uppercase text-mist-500">عقود تنتهي خلال 30 يوماً</p>
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
                                                class="ui-btn ui-btn-secondary ui-btn-sm"
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
                /*
                 * Colours follow the identity and were checked with the dataviz
                 * validator: brand orange #c2410c (the text-safe step, 5.2:1 on
                 * white) and accent blue #0369a1 pass the lightness band, chroma
                 * floor, colour-blind separation (ΔE 20) and 3:1 against the card.
                 * Absence is blue rather than red so a series is never mistaken
                 * for a status colour.
                 */
                const ink = '#222222';
                const muted = '#666666';
                const grid = '#e9ecef';
                const orange = '#c2410c';
                const blue = '#0369a1';

                if (window.Chart) {
                    Chart.defaults.font.family = "'Poppins', 'Tajawal', sans-serif";
                    Chart.defaults.font.size = 13;
                    Chart.defaults.color = muted;
                }

                const tooltip = {
                    rtl: true,
                    textDirection: 'rtl',
                    backgroundColor: '#ffffff',
                    titleColor: ink,
                    bodyColor: ink,
                    borderColor: '#dee2e6',
                    borderWidth: 1,
                    padding: 10,
                    boxPadding: 6,
                    usePointStyle: true,
                };

                const attendanceEl = document.getElementById('attendanceChart');
                if (attendanceEl && window.Chart) {
                    const series = (label, data, colour) => ({
                        label,
                        data,
                        borderColor: colour,
                        backgroundColor: colour + '14',
                        borderWidth: 2,
                        pointRadius: 4,
                        pointHoverRadius: 6,
                        pointBackgroundColor: colour,
                        pointBorderColor: '#ffffff',
                        pointBorderWidth: 2,
                        tension: 0.35,
                        fill: true,
                    });

                    new Chart(attendanceEl, {
                        type: 'line',
                        data: {
                            labels: attendance.labels,
                            datasets: [
                                series('حضور', attendance.present, orange),
                                series('غياب', attendance.absent, blue),
                            ],
                        },
                        options: {
                            responsive: true,
                            maintainAspectRatio: false,
                            interaction: { mode: 'index', intersect: false },
                            plugins: {
                                legend: { rtl: true, textDirection: 'rtl', labels: { color: ink, usePointStyle: true, pointStyle: 'circle', boxWidth: 8, padding: 16 } },
                                tooltip,
                            },
                            scales: {
                                x: { ticks: { color: muted }, grid: { display: false }, border: { color: '#dee2e6' } },
                                y: { beginAtZero: true, ticks: { color: muted, precision: 0 }, grid: { color: grid }, border: { display: false } },
                            },
                        },
                    });
                }

                /*
                 * Department sizes as a sorted horizontal bar chart, not a
                 * doughnut: lengths on one baseline compare at a glance, and a
                 * single series needs one colour instead of an eight-hue palette.
                 */
                const departmentEl = document.getElementById('departmentChart');
                if (departmentEl && window.Chart) {
                    const rows = departments.labels
                        .map((label, i) => ({ label, value: Number(departments.values[i] ?? 0) }))
                        .sort((a, b) => b.value - a.value);

                    new Chart(departmentEl, {
                        type: 'bar',
                        data: {
                            labels: rows.length ? rows.map((row) => row.label) : ['بدون أقسام'],
                            datasets: [{
                                label: 'عدد الموظفين',
                                data: rows.length ? rows.map((row) => row.value) : [0],
                                backgroundColor: orange,
                                hoverBackgroundColor: '#9a3412',
                                borderRadius: 4,
                                borderSkipped: 'start',
                                maxBarThickness: 18,
                            }],
                        },
                        options: {
                            indexAxis: 'y',
                            responsive: true,
                            maintainAspectRatio: false,
                            plugins: { legend: { display: false }, tooltip },
                            scales: {
                                x: { beginAtZero: true, reverse: true, ticks: { color: muted, precision: 0 }, grid: { color: grid }, border: { display: false } },
                                y: { position: 'right', ticks: { color: ink }, grid: { display: false }, border: { color: '#dee2e6' } },
                            },
                        },
                    });
                }
            })();
        </script>
    @endpush
</x-layouts.app>
