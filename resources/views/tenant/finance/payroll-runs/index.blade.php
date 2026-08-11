@php
    use App\Domain\Finance\Enums\PayrollRunStatus;

    $statusClasses = [
        PayrollRunStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        PayrollRunStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        PayrollRunStatus::Approved->value => 'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300',
        PayrollRunStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
        PayrollRunStatus::Cancelled->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="مسيرات الرواتب">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">مسيرات الرواتب</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إعداد واعتماد وصرف مسيرات الرواتب الشهرية.</p>
            </div>
            @can('finance.payroll.prepare')
                <a href="{{ route('finance.payroll-runs.create') }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">
                    إنشاء مسيرة جديدة
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('finance.payroll-runs.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <div>
                <label for="search" class="mb-1.5 block text-xs font-medium text-mist-500">بحث بالفترة</label>
                <input id="search" type="search" name="search" dir="ltr" placeholder="2026-08" value="{{ $filters['search'] }}" class="rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-mist-500">الحالة</label>
                <select id="status" name="status" class="rounded-xl border border-mist-200 bg-white px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900 dark:text-ink-50">
                    <option value="all" @selected($filters['status'] === 'all')>الكل</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="rounded-xl bg-ink-900 px-4 py-2 text-sm font-semibold text-white dark:bg-ink-50 dark:text-ink-900">تصفية</button>
        </form>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الفترة</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">عدد الموظفين</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الإجمالي</th>
                        <th class="px-4 py-3 text-end text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الصافي</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">المُعِد</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الحالة</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($runs as $run)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($runs->currentPage() - 1) * $runs->perPage() }}
                            </td>
                            <td class="px-4 py-3 text-start font-medium text-ink-900 dark:text-ink-50">
                                <x-ui.ltr>{{ $run->period }}</x-ui.ltr>
                            </td>
                            <td class="px-4 py-3 text-center text-mist-500">
                                <x-ui.ltr>{{ $run->payslip_count }}</x-ui.ltr>
                            </td>
                            <td class="px-4 py-3 text-end text-mist-500">
                                <x-ui.money :amount="$run->total_gross" :currency="$run->currency" />
                            </td>
                            <td class="px-4 py-3 text-end font-semibold text-ink-900 dark:text-ink-50">
                                <x-ui.money :amount="$run->total_net" :currency="$run->currency" />
                            </td>
                            <td class="px-4 py-3 text-start text-mist-500">{{ $run->maker?->name ?? '—' }}</td>
                            <td class="px-4 py-3 text-center">
                                <span @class(['inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$run->status->value] ?? ''])>
                                    {{ $run->status->label() }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('finance.payroll-runs.show', $run) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">عرض</a>
                                    @can('finance.payroll.prepare')
                                        @if ($run->status->isEditable())
                                            <a href="{{ route('finance.payroll-runs.edit', $run) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">تعديل</a>
                                        @endif
                                    @endcan
                                    @can('finance.payroll.delete')
                                        @unless ($run->isLocked())
                                            <form method="POST" action="{{ route('finance.payroll-runs.destroy', $run) }}" data-swal-confirm data-swal-title="حذف مسيرة الرواتب هذه؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="8" icon="💰" message="لا توجد مسيرات رواتب بعد." hint="ابدأ بإنشاء مسيرة لفترة محددة بعد تسوية سجل العمل." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $runs->links() }}</div>
    </div>
</x-layouts.app>
