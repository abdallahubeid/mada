@php
    use App\Domain\Tenancy\Enums\TaskStatus;

    $card = 'rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800';
    $heading = 'font-display text-lg font-semibold text-ink-900 dark:text-ink-50';
    $emptyRow = 'rounded-xl border border-dashed border-mist-200 px-3 py-4 text-center text-xs text-mist-400 dark:border-ink-700';

    $hour = (int) now()->format('H');
    $greeting = $hour < 12 ? 'صباح الخير' : ($hour < 18 ? 'مساء الخير' : 'مساء النور');

    $taskColumnStyles = [
        TaskStatus::Todo->value => 'bg-mist-100 text-mist-700 dark:bg-ink-700 dark:text-mist-200',
        TaskStatus::InProgress->value => 'bg-sky-400/15 text-sky-800 dark:text-sky-300',
        TaskStatus::Review->value => 'bg-amber-400/15 text-amber-800 dark:text-amber-300',
        TaskStatus::Completed->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-400',
    ];
@endphp

<x-layouts.app title="لوحتي">
    @if ($employee === null)
        <div class="mx-auto max-w-2xl">
            <div class="{{ $card }} text-center">
                <div class="mx-auto flex h-14 w-14 items-center justify-center rounded-2xl bg-amber-400/15 text-2xl">👤</div>
                <h1 class="mt-4 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">لوحتي</h1>
                <p class="mt-2 text-sm text-mist-500">
                    حسابك غير مرتبط بملف موظف، لذا لا تتوفر بيانات شخصية لعرضها. تواصل مع إدارة الموارد البشرية لربط حسابك.
                </p>
            </div>
        </div>
    @else
        <div class="space-y-6">
            {{-- Hero + check in/out --}}
            <section class="relative overflow-hidden rounded-2xl border border-mist-200 bg-gradient-to-br from-emerald-400/20 via-white to-sky-400/10 p-5 shadow-sm dark:border-ink-600 dark:from-emerald-400/10 dark:via-ink-800 dark:to-ink-800 sm:p-6">
                <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
                    <div class="flex items-start gap-4">
                        <img src="{{ $employee->avatarUrl() }}" alt="" class="h-16 w-16 rounded-full object-cover ring-2 ring-white shadow-sm dark:ring-ink-600">
                        <div>
                            <p class="text-sm font-medium text-emerald-700 dark:text-emerald-300">{{ $greeting }}</p>
                            <h1 class="mt-1 font-display text-2xl font-bold text-ink-900 dark:text-ink-50">{{ $employee->full_name }}</h1>
                            <p class="mt-1 text-sm text-mist-500">
                                {{ $employee->job_title }}
                                @if ($employee->department)
                                    · {{ $employee->department->name }}
                                @endif
                            </p>
                        </div>
                    </div>

                    <div class="flex flex-wrap items-center gap-2" data-testid="attendance-action">
                        @can('hr.attendance.check_in_out')
                            @if ($todayAttendance?->check_in === null)
                                <form method="POST" action="{{ route('tenant.hr.my-attendance.check-in') }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl bg-emerald-400 px-5 py-2.5 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">تسجيل حضور</button>
                                </form>
                            @elseif ($todayAttendance->check_out === null)
                                <form method="POST" action="{{ route('tenant.hr.my-attendance.check-out') }}">
                                    @csrf
                                    <button type="submit" class="rounded-xl border border-amber-400/50 bg-amber-400/15 px-5 py-2.5 text-sm font-semibold text-amber-900 transition hover:bg-amber-400/25 dark:text-amber-200">تسجيل انصراف</button>
                                </form>
                                <span class="text-xs text-mist-500" dir="ltr">حضور: {{ $todayAttendance->check_in?->format('H:i') }}</span>
                            @else
                                <span class="rounded-xl border border-mist-200 bg-white/70 px-4 py-2 text-sm font-semibold text-mist-600 dark:border-ink-600 dark:bg-ink-900/50 dark:text-mist-300">اكتمل حضور اليوم</span>
                                <span class="text-xs text-mist-500" dir="ltr">{{ $todayAttendance->check_in?->format('H:i') }} → {{ $todayAttendance->check_out?->format('H:i') }}</span>
                            @endif
                        @endcan
                    </div>
                </div>
            </section>

            {{-- Personal KPIs --}}
            <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
                <div class="{{ $card }}">
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">رصيد الإجازات المتبقي</p>
                    <p class="mt-2 font-display text-3xl font-bold text-emerald-600 dark:text-emerald-400" data-testid="kpi-leave-balance">{{ $remainingLeaveDays }}</p>
                    <p class="mt-1 text-xs text-mist-500">يوم · {{ now()->year }}</p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">مهامي المفتوحة</p>
                    <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" data-testid="kpi-open-tasks">{{ $tasks['open'] }}</p>
                    <p class="mt-1 text-xs {{ $tasks['overdue'] > 0 ? 'font-semibold text-danger-solid' : 'text-mist-500' }}" data-testid="kpi-overdue-tasks">
                        {{ $tasks['overdue'] > 0 ? $tasks['overdue'].' متأخرة' : 'لا مهام متأخرة' }}
                    </p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">حضوري هذا الشهر</p>
                    <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" data-testid="kpi-month-attendance">{{ $monthAttendance['total'] }}</p>
                    <p class="mt-1 text-xs text-mist-500">يوم مسجّل · {{ $monthAttendance['late'] }} تأخير</p>
                </div>
                <div class="{{ $card }}">
                    <p class="text-sm font-medium text-mist-500 dark:text-mist-400">آخر تقييم</p>
                    @if ($latestEvaluation)
                        <p class="mt-2 font-display text-3xl font-bold text-ink-900 dark:text-ink-50" data-testid="kpi-latest-rating">
                            {{ $latestEvaluation['evaluation']->rating !== null ? number_format((float) $latestEvaluation['evaluation']->rating, 1) : '—' }}
                            <span class="text-base font-medium text-mist-400">/ 5</span>
                        </p>
                        <p class="mt-1 text-xs text-mist-500">{{ $latestEvaluation['period_label'] }}</p>
                    @else
                        <p class="mt-2 font-display text-3xl font-bold text-mist-400">—</p>
                        <p class="mt-1 text-xs text-mist-500">لا يوجد تقييم منشور</p>
                    @endif
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                {{-- Scrum summary --}}
                <div class="{{ $card }} lg:col-span-2">
                    <div class="flex items-center justify-between">
                        <h3 class="{{ $heading }}">مهامي</h3>
                        <a href="{{ route('tenant.hr.my-tasks') }}" class="text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400">فتح اللوحة</a>
                    </div>
                    <div class="mt-4 grid gap-3 sm:grid-cols-2 xl:grid-cols-4">
                        @foreach (TaskStatus::cases() as $status)
                            <div class="rounded-xl bg-mist-50 p-4 dark:bg-ink-900/40">
                                <span class="inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold {{ $taskColumnStyles[$status->value] }}">{{ $status->label() }}</span>
                                <p class="mt-2 font-display text-2xl font-bold text-ink-900 dark:text-ink-50" data-testid="my-tasks-{{ $status->value }}">
                                    {{ $tasks['by_status'][$status->value] }}
                                </p>
                            </div>
                        @endforeach
                    </div>
                    @if ($tasks['next_due'])
                        <p class="mt-4 rounded-xl bg-amber-400/10 px-3 py-2 text-xs text-amber-900 dark:text-amber-200" data-testid="next-due-task">
                            المهمة القادمة: <span class="font-semibold">{{ $tasks['next_due']->title }}</span>
                            — تستحق <span dir="ltr">{{ $tasks['next_due']->due_date?->format('Y-m-d') }}</span>
                        </p>
                    @endif
                </div>

                {{-- Leave balances --}}
                <div class="{{ $card }}">
                    <div class="flex items-center justify-between">
                        <h3 class="{{ $heading }}">أرصدة الإجازات</h3>
                        <a href="{{ route('tenant.hr.my-leaves') }}" class="text-xs font-semibold text-emerald-600 hover:underline dark:text-emerald-400">طلب إجازة</a>
                    </div>
                    <ul class="mt-4 space-y-3 text-sm" data-testid="leave-balances">
                        @forelse ($leaveBalances as $balance)
                            <li>
                                <div class="flex items-center justify-between">
                                    <span class="text-mist-600 dark:text-mist-300">{{ $balance['type']->name }}</span>
                                    <span class="font-semibold tabular-nums">{{ $balance['remaining'] }}/{{ $balance['annual'] }}</span>
                                </div>
                                <div class="mt-1 h-1.5 overflow-hidden rounded-full bg-mist-100 dark:bg-ink-700">
                                    <div class="h-full rounded-full bg-emerald-400" style="width: {{ $balance['annual'] > 0 ? min(100, ($balance['remaining'] / $balance['annual']) * 100) : 0 }}%"></div>
                                </div>
                            </li>
                        @empty
                            <li class="{{ $emptyRow }}">لا توجد أنواع إجازات معرّفة.</li>
                        @endforelse
                    </ul>
                </div>
            </div>

            <div class="grid gap-4 lg:grid-cols-3">
                {{-- Pending leave requests --}}
                <div class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">طلبات إجازتي المعلّقة</h3>
                    <ul class="mt-3 space-y-2 text-sm" data-testid="my-pending-leaves">
                        @forelse ($pendingLeaves as $leave)
                            <li class="flex items-center justify-between gap-2">
                                <span class="truncate">{{ $leave->leaveType?->name ?? 'إجازة' }}</span>
                                <span class="shrink-0 text-xs tabular-nums text-mist-500" dir="ltr">{{ $leave->start_date?->format('Y-m-d') }}</span>
                            </li>
                        @empty
                            <li class="{{ $emptyRow }}">لا توجد طلبات معلّقة.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- My assets --}}
                <div class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">العُهد المسندة لي</h3>
                    <ul class="mt-3 space-y-2 text-sm" data-testid="my-assets">
                        @forelse ($myAssets as $assignment)
                            <li class="flex items-center justify-between gap-2">
                                <span class="truncate">{{ $assignment->asset?->name ?? '—' }}</span>
                                <span class="shrink-0 text-xs text-mist-500" dir="ltr">{{ $assignment->asset?->asset_code }}</span>
                            </li>
                        @empty
                            <li class="{{ $emptyRow }}">لا توجد عُهد مسندة لك.</li>
                        @endforelse
                    </ul>
                </div>

                {{-- Contract --}}
                <div class="{{ $card }}">
                    <h3 class="text-sm font-semibold text-ink-900 dark:text-ink-50">عقدي</h3>
                    @if ($activeContract)
                        <dl class="mt-3 space-y-2 text-sm">
                            <div class="flex justify-between"><dt class="text-mist-500">الحالة</dt><dd class="font-medium">{{ $activeContract->status->label() }}</dd></div>
                            <div class="flex justify-between"><dt class="text-mist-500">النوع</dt><dd class="font-medium">{{ $activeContract->contract_type?->label() ?? '—' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-mist-500">ينتهي</dt><dd class="font-medium tabular-nums" dir="ltr">{{ $activeContract->end_date?->format('Y-m-d') ?? 'مفتوح' }}</dd></div>
                            <div class="flex justify-between"><dt class="text-mist-500">نهاية التجربة</dt><dd class="font-medium tabular-nums" dir="ltr">{{ $activeContract->probation_end_date?->format('Y-m-d') ?? '—' }}</dd></div>
                        </dl>
                    @else
                        <p class="mt-3 {{ $emptyRow }}">لا يوجد عقد ساري.</p>
                    @endif
                </div>
            </div>

            {{-- Announcements --}}
            @can('tenant.announcements.view_self')
                <div class="{{ $card }}">
                    <h3 class="{{ $heading }}">التعميمات</h3>
                    <ul class="mt-4 space-y-3" data-testid="announcements">
                        @forelse ($announcements as $announcement)
                            <li class="rounded-xl bg-mist-50 px-4 py-3 dark:bg-ink-900/40">
                                <div class="flex items-center gap-2">
                                    @if ($announcement->is_pinned)
                                        <span class="text-xs">📌</span>
                                    @endif
                                    <p class="text-sm font-semibold text-ink-900 dark:text-ink-50">{{ $announcement->title }}</p>
                                </div>
                                <p class="mt-1 line-clamp-2 text-xs text-mist-500">{{ strip_tags($announcement->content) }}</p>
                            </li>
                        @empty
                            <li class="{{ $emptyRow }}">لا توجد تعميمات منشورة.</li>
                        @endforelse
                    </ul>
                </div>
            @endcan
        </div>
    @endif
</x-layouts.app>
