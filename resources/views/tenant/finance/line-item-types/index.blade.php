@php
    use App\Domain\Finance\Enums\PayslipLineItemKind;

    $kindClasses = [
        PayslipLineItemKind::Allowance->value => 'bg-brand-500/15 text-brand-700 dark:text-brand-300',
        PayslipLineItemKind::Deduction->value => 'bg-danger-solid/10 text-danger-solid',
    ];
@endphp

<x-layouts.app title="بنود البدلات والاستقطاعات">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">بنود البدلات والاستقطاعات</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                    تُطبَّق البنود المفعّلة ذات القيمة غير الصفرية تلقائياً على كل قسيمة عند إنشاء مسيرة جديدة.
                </p>
            </div>
            <a href="{{ route('finance.line-item-types.create') }}" class="inline-flex items-center justify-center rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600">
                إضافة بند
            </a>
        </div>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">البند</th>
                        <th class="px-3 py-2 text-start text-xs font-medium text-mist-500 dark:text-mist-400">الرمز</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">النوع</th>
                        <th class="px-3 py-2 text-end text-xs font-medium text-mist-500 dark:text-mist-400">القيمة الافتراضية</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">الحالة</th>
                        <th class="px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($types as $type)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($types->currentPage() - 1) * $types->perPage() }}
                            </td>
                            <td class="px-3 py-2 text-start font-medium text-ink-900 dark:text-ink-50">{{ $type->name }}</td>
                            <td class="px-3 py-2 text-start text-mist-500"><x-ui.ltr>{{ $type->code ?? '—' }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-center">
                                <span @class(['inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold', $kindClasses[$type->kind->value] ?? ''])>
                                    {{ $type->kind->label() }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-end text-ink-700 dark:text-mist-200">
                                <x-ui.money :amount="$type->default_amount" :signed="true" />
                            </td>
                            <td class="px-3 py-2 text-center">
                                <span @class([
                                    'inline-flex rounded-md px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-brand-500/15 text-brand-700 dark:text-brand-300' => $type->is_active,
                                    'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300' => ! $type->is_active,
                                ])>
                                    {{ $type->is_active ? 'مفعّل' : 'معطّل' }}
                                </span>
                            </td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('finance.line-item-types.edit', $type) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600">تعديل</a>
                                    <form method="POST" action="{{ route('finance.line-item-types.destroy', $type) }}" data-swal-confirm data-swal-title="حذف هذا البند؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="7" icon="receipt" message="لا توجد بنود بعد." hint="أضف بدلاً أو استقطاعاً ليُطبَّق تلقائياً على المسيرات الجديدة." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $types->links() }}</div>
    </div>
</x-layouts.app>
