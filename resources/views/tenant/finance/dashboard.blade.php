@php
    use App\Domain\Finance\Enums\PayrollRunStatus;

    $statusClasses = [
        PayrollRunStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        PayrollRunStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        PayrollRunStatus::Approved->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        PayrollRunStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
        PayrollRunStatus::Cancelled->value => 'bg-danger-solid/10 text-danger-solid',
    ];

    $peak = collect($trend)->max('total') ?: 1;
@endphp

<x-layouts.app title="لوحة التحكم المالية">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">لوحة التحكم المالية</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">ملخص تكاليف الرواتب المعتمدة والمصروفة.</p>
            </div>
            @can('finance.payroll.prepare')
                <a href="{{ route('finance.payroll-runs.create') }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                    إنشاء مسيرة جديدة
                </a>
            @endcan
        </div>

        {{--
            Cost side only. Revenue and Net Profit tiles are absent rather than
            zeroed — Clients/Invoicing are Phase 2B (ADR-18), and a zero would
            read as "no revenue" instead of "not tracked here yet" (BR-607).
        --}}
        <div class="rounded-2xl border border-sky-300/60 bg-sky-50/70 p-3 text-xs text-sky-800 dark:border-sky-500/30 dark:bg-sky-500/10 dark:text-sky-200">
            تعرض هذه اللوحة جانب التكاليف فقط. الإيرادات وصافي الربح تُضاف مع وحدة الفوترة في المرحلة الثانية-ب.
        </div>

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'إجمالي المصروف', 'value' => $kpis['total_disbursed'], 'money' => true, 'tone' => 'text-sky-600 dark:text-sky-400'],
                ['label' => 'المعتمد والمصروف', 'value' => $kpis['total_approved'], 'money' => true, 'tone' => 'text-ink-900 dark:text-ink-50'],
                ['label' => 'خصومات الغياب', 'value' => $kpis['total_absence_deductions'], 'money' => true, 'tone' => 'text-danger-solid'],
                ['label' => 'قسائم مصروفة', 'value' => $kpis['employees_paid'], 'money' => false, 'tone' => 'text-ink-900 dark:text-ink-50'],
            ] as $tile)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ $tile['label'] }}</p>
                    <p @class(['mt-2 font-display text-xl font-bold', $tile['tone']])>
                        @if ($tile['money'])
                            <x-ui.money :amount="$tile['value']" :currency="$currency" />
                        @else
                            <x-ui.ltr>{{ $tile['value'] }}</x-ui.ltr>
                        @endif
                    </p>
                </div>
            @endforeach
        </div>

        @if ($pendingApproval->isNotEmpty())
            <div class="rounded-2xl border border-amber-300/60 bg-amber-50/80 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">
                    مسيرات بانتظار الاعتماد ({{ $pendingApproval->count() }})
                </p>
                <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-300">
                    @foreach ($pendingApproval as $pending)
                        <li class="flex flex-wrap items-center gap-2">
                            <a href="{{ route('finance.payroll-runs.show', $pending) }}" class="font-semibold underline">
                                <x-ui.ltr>{{ $pending->period }}</x-ui.ltr>
                            </a>
                            <span>— أعدّها {{ $pending->maker?->name ?? '—' }}</span>
                            <span>·</span>
                            <x-ui.money :amount="$pending->total_net" :currency="$pending->currency" />
                        </li>
                    @endforeach
                </ul>
            </div>
        @endif

        <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
            @foreach ([
                ['label' => 'المصروفات المعتمدة', 'value' => $expenses['total'], 'tone' => 'text-ink-900 dark:text-ink-50'],
                ['label' => 'مطالبات لم تُصرف بعد', 'value' => $expenses['unpaid_claims'], 'tone' => 'text-amber-600 dark:text-amber-400'],
                ['label' => 'التزام نهاية الخدمة', 'value' => $offboarding['committed'], 'tone' => 'text-danger-solid'],
                ['label' => 'تسويات مصروفة', 'value' => $offboarding['paid'], 'tone' => 'text-sky-600 dark:text-sky-400'],
            ] as $tile)
                <div class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <p class="text-xs font-medium uppercase tracking-wider text-mist-500 dark:text-mist-400">{{ $tile['label'] }}</p>
                    <p @class(['mt-2 font-display text-xl font-bold', $tile['tone']])>
                        <x-ui.money :amount="$tile['value']" :currency="$currency" />
                    </p>
                </div>
            @endforeach
        </div>

        @if ($expenses['pending_count'] > 0 || $offboarding['pending_count'] > 0)
            <div class="rounded-2xl border border-amber-300/60 bg-amber-50/80 p-4 dark:border-amber-500/30 dark:bg-amber-500/10">
                <p class="text-sm font-semibold text-amber-800 dark:text-amber-200">بانتظار قرارك</p>
                <ul class="mt-2 space-y-1 text-sm text-amber-700 dark:text-amber-300">
                    @if ($expenses['pending_count'] > 0)
                        <li>
                            <a href="{{ route('finance.expenses.index', ['status' => 'pending_approval']) }}" class="font-semibold underline">
                                {{ $expenses['pending_count'] }} مصروف بانتظار الاعتماد
                            </a>
                            — <x-ui.money :amount="$expenses['pending_total']" :currency="$currency" />
                        </li>
                    @endif
                    @if ($offboarding['pending_count'] > 0)
                        <li>
                            <a href="{{ route('finance.offboarding.index', ['status' => 'pending_approval']) }}" class="font-semibold underline">
                                {{ $offboarding['pending_count'] }} تسوية نهاية خدمة بانتظار الاعتماد
                            </a>
                        </li>
                    @endif
                </ul>
            </div>
        @endif

        <div class="grid gap-6 lg:grid-cols-2">
            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">تكلفة الرواتب الشهرية</h2>
                <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">المسيرات المعتمدة والمصروفة فقط.</p>

                <div class="mt-4 space-y-3">
                    @foreach ($trend as $month)
                        <div>
                            <div class="flex items-center justify-between text-xs">
                                <span class="text-mist-500 dark:text-mist-400"><x-ui.ltr>{{ $month['period'] }}</x-ui.ltr></span>
                                <span class="font-semibold text-ink-700 dark:text-mist-200">
                                    <x-ui.money :amount="$month['total']" />
                                </span>
                            </div>
                            <div class="mt-1 h-2 w-full overflow-hidden rounded-full bg-mist-100 dark:bg-ink-900">
                                <div class="h-full rounded-full bg-emerald-400" style="width: {{ $peak > 0 ? max(2, (int) round($month['total'] / $peak * 100)) : 2 }}%"></div>
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

            <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">توزيع المسيرات حسب الحالة</h2>

                <div class="mt-4 w-full overflow-x-auto">
                    <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                        <thead class="bg-mist-50 dark:bg-ink-900">
                            <tr>
                                <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                                <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الحالة</th>
                                <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">العدد</th>
                                <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الصافي</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                            @foreach ($statusBreakdown as $row)
                                <tr>
                                    <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                    <td class="px-4 py-3 text-start">
                                        <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$row['status']->value] ?? ''])>
                                            {{ $row['status']->label() }}
                                        </span>
                                    </td>
                                    <td class="px-4 py-3 text-center text-mist-500"><x-ui.ltr>{{ $row['count'] }}</x-ui.ltr></td>
                                    <td class="px-4 py-3 text-end text-ink-700 dark:text-mist-200"><x-ui.money :amount="$row['total']" /></td>
                                </tr>
                            @endforeach
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">المصروفات حسب التصنيف</h2>

            <div class="mt-4 w-full overflow-x-auto">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">التصنيف</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">العدد</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الإجمالي</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($expenses['by_category'] as $row)
                            <tr>
                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-start text-ink-700 dark:text-mist-200">{{ $row['name'] }}</td>
                                <td class="px-4 py-3 text-center text-mist-500"><x-ui.ltr>{{ $row['count'] }}</x-ui.ltr></td>
                                <td class="px-4 py-3 text-end font-semibold text-ink-900 dark:text-ink-50"><x-ui.money :amount="$row['total']" :currency="$currency" /></td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="4" icon="🧾" message="لا توجد مصروفات معتمدة بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="rounded-2xl border border-mist-200 bg-white p-5 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h2 class="text-sm font-semibold text-ink-900 dark:text-ink-50">أعلى الأقسام تكلفة</h2>
            <p class="mt-1 text-xs text-mist-500 dark:text-mist-400">من بيانات القسائم المجمّدة، لا من سجل الموظفين الحالي.</p>

            <div class="mt-4 w-full overflow-x-auto">
                <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                    <thead class="bg-mist-50 dark:bg-ink-900">
                        <tr>
                            <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">القسم</th>
                            <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">عدد القسائم</th>
                            <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">التكلفة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($topCosts as $cost)
                            <tr>
                                <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-4 py-3 text-start text-ink-700 dark:text-mist-200">{{ $cost['department'] }}</td>
                                <td class="px-4 py-3 text-center text-mist-500"><x-ui.ltr>{{ $cost['headcount'] }}</x-ui.ltr></td>
                                <td class="px-4 py-3 text-end font-semibold text-ink-900 dark:text-ink-50"><x-ui.money :amount="$cost['total']" :currency="$currency" /></td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="4" icon="🏢" message="لا توجد مسيرات معتمدة بعد." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </div>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الفترة</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الحالة</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الصافي</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($recentRuns as $run)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-4 py-3 text-start font-medium text-ink-900 dark:text-ink-50"><x-ui.ltr>{{ $run->period }}</x-ui.ltr></td>
                            <td class="px-4 py-3 text-center">
                                <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$run->status->value] ?? ''])>
                                    {{ $run->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-end font-semibold text-ink-900 dark:text-ink-50"><x-ui.money :amount="$run->total_net" :currency="$run->currency" /></td>
                            <td class="px-4 py-3 text-center">
                                <a href="{{ route('finance.payroll-runs.show', $run) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="5" icon="💰" message="لا توجد مسيرات رواتب بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>
    </div>
</x-layouts.app>
