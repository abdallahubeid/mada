@php
    use App\Domain\Finance\Enums\ExpenseStatus;

    $statusClasses = [
        ExpenseStatus::Draft->value => 'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300',
        ExpenseStatus::PendingApproval->value => 'bg-amber-400/15 text-amber-700 dark:text-amber-300',
        ExpenseStatus::Approved->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        ExpenseStatus::Rejected->value => 'bg-danger-solid/10 text-danger-solid',
        ExpenseStatus::Paid->value => 'bg-sky-400/15 text-sky-700 dark:text-sky-300',
    ];
@endphp

<x-layouts.app title="المصروفات">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">المصروفات</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تسجيل واعتماد وصرف مصروفات المؤسسة ومطالبات الموظفين.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                @can('finance.expense_categories.manage')
                    <a href="{{ route('finance.expense-categories.index') }}" class="ui-btn ui-btn-secondary">التصنيفات</a>
                @endcan
                @can('finance.expenses.manage')
                    <a href="{{ route('finance.expenses.create') }}" class="ui-btn ui-btn-primary">تسجيل مصروف</a>
                @endcan
            </div>
        </div>

        <form method="GET" action="{{ route('finance.expenses.index') }}" class="ui-card flex flex-wrap items-end gap-3 p-4">
            <div>
                <label for="search" class="mb-1.5 block text-xs font-medium text-mist-500">بحث</label>
                <input id="search" type="search" name="search" value="{{ $filters['search'] }}" class="ui-control">
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
            <div>
                <label for="category" class="mb-1.5 block text-xs font-medium text-mist-500">التصنيف</label>
                <select id="category" name="category" class="ui-control">
                    <option value="">الكل</option>
                    @foreach ($categories as $category)
                        <option value="{{ $category->id }}" @selected($filters['category'] === (string) $category->id)>{{ $category->name }}</option>
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
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">البيان</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">التصنيف</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">التاريخ</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">مطالبة</th>
                        <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">القيمة</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">الحالة</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($expenses as $expense)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($expenses->currentPage() - 1) * $expenses->perPage() }}
                            </td>
                            <td class="px-3 py-2 text-start font-medium text-ink-900 dark:text-ink-50">{{ $expense->title }}</td>
                            <td class="px-3 py-2 text-start text-mist-500">{{ $expense->category?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-start text-mist-500"><x-ui.ltr>{{ $expense->expense_date?->format('Y-m-d') }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-center text-mist-500">{{ $expense->is_claimable ? 'نعم' : 'لا' }}</td>
                            <td class="px-3 py-2 text-end font-semibold text-ink-900 dark:text-ink-50">
                                <x-ui.money :amount="$expense->amount" :currency="$expense->currency" />
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $statusClasses[$expense->status->value] ?? ''])>
                                    {{ $expense->status->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('finance.expenses.show', $expense) }}" class="ui-btn ui-btn-secondary ui-btn-sm">عرض</a>
                                    @can('finance.expenses.manage')
                                        @if ($expense->status->isEditable())
                                            <a href="{{ route('finance.expenses.edit', $expense) }}" class="ui-btn ui-btn-secondary ui-btn-sm">تعديل</a>
                                            <form method="POST" action="{{ route('finance.expenses.destroy', $expense) }}" data-swal-confirm data-swal-title="حذف هذا المصروف؟">
                                                @csrf
                                                @method('DELETE')
                                                <button type="submit" class="ui-btn ui-btn-danger-outline ui-btn-sm">حذف</button>
                                            </form>
                                        @endif
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="8" icon="receipt" message="لا توجد مصروفات بعد." hint="سجّل أول مصروف ليبدأ مسار الاعتماد." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $expenses->links() }}</div>
    </div>
</x-layouts.app>
