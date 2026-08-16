@php
    use App\Domain\Tenancy\Enums\TaskStatus;

    $user = auth()->user();
    $card = 'rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800';
    $heading = 'font-display text-lg font-medium text-ink-900 dark:text-ink-50';
    $emptyRow = 'rounded-xl border border-dashed border-mist-200 px-3 py-4 text-center text-xs text-mist-400 dark:border-ink-700';

    $taskColumnStyles = [
        TaskStatus::Todo->value => 'bg-mist-100 text-mist-700 dark:bg-ink-700 dark:text-mist-200',
        TaskStatus::InProgress->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
        TaskStatus::Review->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        TaskStatus::Completed->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
    ];
@endphp

<x-layouts.app title="لوحة الموارد البشرية">
    <div class="space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-end sm:justify-between">
            <div>
                <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">
                    لوحة الموارد البشرية
                </h2>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    نظرة تشغيلية على القوى العاملة اليوم — {{ now()->translatedFormat('l، j F Y') }}
                </p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('hr.employees.create')
                    <a href="{{ route('hr.employees.create') }}" class="inline-flex items-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">إضافة موظف</a>
                @endcan
                @can('hr.attendance.view_any')
                    <a href="{{ route('hr.attendance.index') }}" class="inline-flex items-center rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600 dark:text-mist-200">سجل الحضور</a>
                @endcan
                @can('tenant.reports.view')
                    <a href="{{ route('tenant.reports.index') }}" class="inline-flex items-center rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold text-ink-700 transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600 dark:text-mist-200">التقارير</a>
                @endcan
            </div>
        </div>

        {{-- KPI strip --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <div class="{{ $card }}">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">القوى العاملة</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-headcount">{{ $kpis['headcount'] }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">حاضرون اليوم</p>
                <p class="mt-2 font-display text-3xl font-medium text-brand-600 dark:text-brand-300" data-testid="kpi-present-today">{{ $kpis['present_today'] }}</p>
            </div>
            <div class="{{ $card }}">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">نسبة الغياب اليوم</p>
                <p class="mt-2 font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="kpi-absence-rate">{{ $kpis['absence_rate'] }}%</p>
            </div>
            <div class="{{ $card }}">
                <p class="text-sm font-medium text-mist-500 dark:text-mist-400">طلبات إجازة معلّقة</p>
                <p class="mt-2 font-display text-3xl font-medium text-amber-600 dark:text-amber-400" data-testid="kpi-pending-leaves">{{ $kpis['pending_leaves'] }}</p>
            </div>
        </div>

        {{-- Today's attendance split --}}
        <div class="{{ $card }}">
            <h3 class="{{ $heading }}">توزيع حضور اليوم</h3>
            <div class="mt-4 grid gap-3 sm:grid-cols-3 xl:grid-cols-5">
                @foreach ([
                    ['حاضر', $attendanceToday['present'], 'text-brand-600 dark:text-brand-300', 'present'],
                    ['متأخر', $attendanceToday['late'], 'text-amber-600 dark:text-amber-400', 'late'],
                    ['نصف يوم', $attendanceToday['half_day'], 'text-sky-600 dark:text-sky-400', 'half-day'],
                    ['غائب', $attendanceToday['absent'], 'text-danger-solid', 'absent'],
                    ['لم يسجّل', $attendanceToday['no_record'], 'text-mist-500', 'no-record'],
                ] as [$label, $value, $tone, $slug])
                    <div class="rounded-xl bg-mist-50 p-3 text-center dark:bg-ink-900/40">
                        <p class="text-xs font-medium text-mist-500">{{ $label }}</p>
                        <p class="mt-1 font-display text-2xl font-medium {{ $tone }}" data-testid="attendance-{{ $slug }}">{{ $value }}</p>
                    </div>
                @endforeach
            </div>
            <p class="mt-3 text-xs text-mist-500">
                «لم يسجّل» تشمل الموظفين النشطين الذين لا يوجد لهم أي سجل حضور اليوم، وتُحتسب ضمن نسبة الغياب.
            </p>
        </div>

        <div class="grid gap-4 lg:grid-cols-3">
            {{-- Pending leave approvals --}}
            <div class="{{ $card }} lg:col-span-2">
                <div class="flex items-center justify-between">
                    <h3 class="{{ $heading }}">طلبات إجازة بانتظار الاعتماد</h3>
                    @can('hr.leaves.view_any')
                        <a href="{{ route('hr.leaves.index', ['status' => 'pending']) }}" class="text-xs font-semibold text-brand-600 hover:underline dark:text-brand-300">عرض الكل</a>
                    @endcan
                </div>
                <ul class="mt-4 space-y-2" data-testid="pending-leaves-list">
                    @forelse ($pendingLeaves as $leave)
                        <li class="flex flex-wrap items-center justify-between gap-2 rounded-xl bg-mist-50 px-3 py-2 text-sm dark:bg-ink-900/40">
                            <div>
                                <span class="font-semibold text-ink-900 dark:text-ink-50">{{ $leave->employee?->full_name ?? '—' }}</span>
                                <span class="text-mist-500"> · {{ $leave->leaveType?->name ?? 'إجازة' }}</span>
                            </div>
                            <div class="flex items-center gap-3 text-xs text-mist-500">
                                <span dir="ltr">{{ $leave->start_date?->format('Y-m-d') }}</span>
                                <span class="rounded-md bg-amber-400/15 px-2 py-0.5 font-bold text-amber-800 dark:text-amber-300">{{ $leave->days_count }} يوم</span>
                            </div>
                        </li>
                    @empty
                        <li class="{{ $emptyRow }}">لا توجد طلبات معلّقة.</li>
                    @endforelse
                </ul>
            </div>

            {{-- Evaluation progress --}}
            <div class="{{ $card }}">
                <h3 class="{{ $heading }}">تقدّم التقييمات</h3>
                <p class="mt-1 text-xs text-mist-500">فترة {{ $evaluations['period_label'] }}</p>
                <div class="mt-4">
                    <div class="flex items-end justify-between">
                        <span class="font-display text-3xl font-medium text-ink-900 dark:text-ink-50" data-testid="evaluation-completion">{{ $evaluations['completion_rate'] }}%</span>
                        <span class="text-xs text-mist-500">{{ $evaluations['approved'] + $evaluations['submitted'] }} / {{ $evaluations['headcount'] }}</span>
                    </div>
                    <div class="mt-2 h-2 overflow-hidden rounded-full bg-mist-100 dark:bg-ink-700">
                        <div class="h-full rounded-full bg-brand-500" style="width: {{ min(100, $evaluations['completion_rate']) }}%"></div>
                    </div>
                </div>
                <dl class="mt-4 space-y-2 text-sm">
                    <div class="flex justify-between"><dt class="text-mist-500">معتمد</dt><dd class="font-semibold" data-testid="evaluation-approved">{{ $evaluations['approved'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-mist-500">مُرسل</dt><dd class="font-semibold" data-testid="evaluation-submitted">{{ $evaluations['submitted'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-mist-500">مسودة</dt><dd class="font-semibold">{{ $evaluations['draft'] }}</dd></div>
                    <div class="flex justify-between"><dt class="text-mist-500">لم يبدأ</dt><dd class="font-semibold" data-testid="evaluation-not-started">{{ $evaluations['not_started'] }}</dd></div>
                </dl>
            </div>
        </div>

        {{-- Task rollup --}}
        <div class="{{ $card }}">
            <div class="flex items-center justify-between">
                <h3 class="{{ $heading }}">حالة مهام الفريق</h3>
                @if ($tasks['overdue'] > 0)
                    <span class="rounded-md bg-danger-solid/10 px-3 py-1 text-xs font-bold text-danger-solid" data-testid="tasks-overdue">
                        {{ $tasks['overdue'] }} متأخرة
                    </span>
                @endif
            </div>
            <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                @foreach (TaskStatus::cases() as $status)
                    <div class="rounded-xl bg-mist-50 p-4 dark:bg-ink-900/40">
                        <span class="inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold {{ $taskColumnStyles[$status->value] }}">
                            {{ $status->label() }}
                        </span>
                        <p class="mt-2 font-display text-2xl font-medium text-ink-900 dark:text-ink-50" data-testid="tasks-{{ $status->value }}">
                            {{ $tasks['by_status'][$status->value] }}
                        </p>
                    </div>
                @endforeach
            </div>
        </div>

        {{-- Watchlists --}}
        <div class="grid gap-4 lg:grid-cols-2 xl:grid-cols-4">
            <div class="{{ $card }}">
                <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">عقود تنتهي خلال 30 يوماً</h3>
                <ul class="mt-3 space-y-2 text-sm" data-testid="expiring-contracts">
                    @forelse ($expiringContracts as $contract)
                        <li class="flex items-center justify-between gap-2">
                            <span class="truncate">{{ $contract->employee?->full_name ?? '—' }}</span>
                            <span class="shrink-0 text-xs tabular-nums text-mist-500" dir="ltr">{{ $contract->end_date?->format('Y-m-d') }}</span>
                        </li>
                    @empty
                        <li class="{{ $emptyRow }}">لا توجد عقود قريبة الانتهاء.</li>
                    @endforelse
                </ul>
            </div>

            <div class="{{ $card }}">
                <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">فترات تجربة تنتهي قريباً</h3>
                <ul class="mt-3 space-y-2 text-sm" data-testid="ending-probations">
                    @forelse ($endingProbations as $contract)
                        <li class="flex items-center justify-between gap-2">
                            <span class="truncate">{{ $contract->employee?->full_name ?? '—' }}</span>
                            <span class="shrink-0 text-xs tabular-nums text-mist-500" dir="ltr">{{ $contract->probation_end_date?->format('Y-m-d') }}</span>
                        </li>
                    @empty
                        <li class="{{ $emptyRow }}">لا توجد فترات تجربة منتهية قريباً.</li>
                    @endforelse
                </ul>
            </div>

            <div class="{{ $card }}">
                <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">في إجازة اليوم</h3>
                <ul class="mt-3 space-y-2 text-sm" data-testid="on-leave-today">
                    @forelse ($onLeaveToday as $person)
                        <li class="truncate">{{ $person->full_name }}</li>
                    @empty
                        <li class="{{ $emptyRow }}">لا أحد في إجازة اليوم.</li>
                    @endforelse
                </ul>
            </div>

            <div class="{{ $card }}">
                <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">ذكرى الالتحاق (30 يوماً)</h3>
                <ul class="mt-3 space-y-2 text-sm" data-testid="anniversaries">
                    @forelse ($anniversaries as $row)
                        <li class="flex items-center justify-between gap-2">
                            <span class="truncate">{{ $row['employee']->full_name }}</span>
                            <span class="shrink-0 rounded-md bg-brand-500/15 px-2 py-0.5 text-xs font-bold text-brand-700 dark:text-brand-300">
                                {{ $row['years'] }} سنة
                            </span>
                        </li>
                    @empty
                        <li class="{{ $emptyRow }}">لا توجد ذكرى التحاق قريبة.</li>
                    @endforelse
                </ul>
            </div>
        </div>
    </div>
</x-layouts.app>
