@php
    use App\Domain\Finance\Enums\PayrollRunStatus;

    $statusClasses = [
        PayrollRunStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        PayrollRunStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        PayrollRunStatus::Approved->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        PayrollRunStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
        PayrollRunStatus::Cancelled->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="مسيرات الرواتب">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">مسيرات الرواتب</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">إعداد واعتماد وصرف مسيرات الرواتب الشهرية.</p>
            </div>
            @can('finance.payroll.prepare')
                <a href="{{ route('finance.payroll-runs.create') }}" class="ui-btn ui-btn-primary">
                    إنشاء مسيرة جديدة
                </a>
            @endcan
        </div>

        <form method="GET" action="{{ route('finance.payroll-runs.index') }}" class="ui-card flex flex-wrap items-end gap-3 p-4">
            <div>
                <label for="search" class="mb-1.5 block text-xs font-medium text-mist-500">بحث بالفترة</label>
                <input id="search" type="search" name="search" dir="ltr" placeholder="2026-08" value="{{ $filters['search'] }}" class="ui-control">
            </div>
            <div>
                <label for="status" class="mb-1.5 block text-xs font-medium text-mist-500">الحالة</label>
                <select id="status" name="status" class="ui-control">
                    <option value="all" @selected($filters['status'] === 'all')>الكل</option>
                    @foreach ($statuses as $status)
                        <option value="{{ $status->value }}" @selected($filters['status'] === $status->value)>{{ $status->label() }}</option>
                    @endforeach
                </select>
            </div>
            <button type="submit" class="ui-btn ui-btn-secondary">تصفية</button>
        </form>

        <div class="ui-card w-full overflow-x-auto">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">الفترة</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">عدد الموظفين</th>
                        <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">الإجمالي</th>
                        <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">الصافي</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">المُعِد</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">الحالة</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($runs as $run)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($runs->currentPage() - 1) * $runs->perPage() }}
                            </td>
                            <td class="px-3 py-2 text-start font-medium text-ink-900 dark:text-ink-50">
                                <x-ui.ltr>{{ $run->period }}</x-ui.ltr>
                            </td>
                            <td class="px-3 py-2 text-center text-mist-500">
                                <x-ui.ltr>{{ $run->payslip_count }}</x-ui.ltr>
                            </td>
                            <td class="px-3 py-2 text-end text-mist-500">
                                <x-ui.money :amount="$run->total_gross" :currency="$run->currency" />
                            </td>
                            <td class="px-3 py-2 text-end font-semibold text-ink-900 dark:text-ink-50">
                                <x-ui.money :amount="$run->total_net" :currency="$run->currency" />
                            </td>
                            <td class="px-3 py-2 text-start text-mist-500">{{ $run->maker?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-center">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$run->status->value] ?? ''])>
                                    {{ $run->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('finance.payroll-runs.show', $run) }}" class="ui-btn ui-btn-secondary ui-btn-sm">عرض</a>
                                    @can('finance.payroll.prepare')
                                        @if ($run->status->isEditable())
                                            <a href="{{ route('finance.payroll-runs.edit', $run) }}" class="ui-btn ui-btn-secondary ui-btn-sm">تعديل</a>
                                        @endif
                                    @endcan
                                    @can('finance.payroll.delete')
                                        @unless ($run->isLocked())
                                            <form method="POST" action="{{ route('finance.payroll-runs.destroy', $run) }}" data-swal-confirm data-swal-title="حذف مسيرة الرواتب هذه؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ui-btn ui-btn-danger-outline ui-btn-sm">حذف</button>
                                            </form>
                                        @endunless
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="8" icon="banknotes" message="لا توجد مسيرات رواتب بعد." hint="ابدأ بإنشاء مسيرة لفترة محددة بعد تسوية سجل العمل." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $runs->links() }}</div>
    </div>
</x-layouts.app>
