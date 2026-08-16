@php
    use App\Domain\Finance\Enums\SettlementStatus;

    $statusClasses = [
        SettlementStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        SettlementStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        SettlementStatus::Approved->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        SettlementStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
        SettlementStatus::Cancelled->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="تسويات نهاية الخدمة">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">تسويات نهاية الخدمة</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    مكافأة نهاية الخدمة، بدل الإجازات غير المستخدمة، وراتب الشهر الأخير.
                </p>
            </div>
            @can('finance.offboarding.manage')
                <a href="{{ route('finance.offboarding.create') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">إعداد تسوية</a>
            @endcan
        </div>

        <form method="GET" action="{{ route('finance.offboarding.index') }}" class="flex flex-wrap items-end gap-3 rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
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
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">الموظف</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">آخر يوم عمل</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">السبب</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">مدة الخدمة</th>
                        <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">صافي التسوية</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">الحالة</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($settlements as $settlement)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($settlements->currentPage() - 1) * $settlements->perPage() }}
                            </td>
                            <td class="px-3 py-2 text-start font-medium text-ink-900 dark:text-ink-50">{{ $settlement->employee_name }}</td>
                            <td class="px-3 py-2 text-start text-mist-500"><x-ui.ltr>{{ $settlement->last_working_day?->format('Y-m-d') }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-start text-mist-500">{{ $settlement->reason->label() }}</td>
                            <td class="px-3 py-2 text-center text-mist-500">{{ $settlement->serviceYearsLabel() }}</td>
                            <td class="px-3 py-2 text-end font-semibold text-ink-900 dark:text-ink-50">
                                <x-ui.money :amount="$settlement->total_amount" :currency="$settlement->currency" />
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$settlement->status->value] ?? ''])>
                                    {{ $settlement->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <a href="{{ route('finance.offboarding.show', $settlement) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600">عرض</a>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="8" icon="logout" message="لا توجد تسويات نهاية خدمة." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $settlements->links() }}</div>
    </div>
</x-layouts.app>
