<x-layouts.app title="تصنيفات المصروفات">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تصنيفات المصروفات</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تُستخدم لتحليل التكاليف في لوحة التحكم المالية.</p>
            </div>
            <a href="{{ route('finance.expense-categories.create') }}" class="inline-flex items-center justify-center rounded-xl bg-emerald-400 px-4 py-2 text-sm font-semibold text-emerald-900 shadow-glow transition hover:bg-emerald-300">إضافة تصنيف</a>
        </div>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">التصنيف</th>
                        <th class="px-4 py-3 text-start text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الرمز</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">عدد المصروفات</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">الحالة</th>
                        <th class="px-4 py-3 text-center text-xs font-semibold uppercase tracking-wider text-mist-500 dark:text-mist-400">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($categories as $category)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-4 py-3 text-center text-sm tabular-nums text-mist-500">
                                {{ $loop->iteration + ($categories->currentPage() - 1) * $categories->perPage() }}
                            </td>
                            <td class="px-4 py-3 text-start font-medium text-ink-900 dark:text-ink-50">{{ $category->name }}</td>
                            <td class="px-4 py-3 text-start text-mist-500"><x-ui.ltr>{{ $category->code ?? '—' }}</x-ui.ltr></td>
                            <td class="px-4 py-3 text-center text-mist-500"><x-ui.ltr>{{ $category->expenses_count }}</x-ui.ltr></td>
                            <td class="px-4 py-3 text-center">
                                <span @class([
                                    'inline-flex rounded-full px-2.5 py-0.5 text-xs font-semibold',
                                    'bg-emerald-400/15 text-emerald-700 dark:text-emerald-300' => $category->is_active,
                                    'bg-mist-200 text-mist-700 dark:bg-ink-700 dark:text-mist-300' => ! $category->is_active,
                                ])>
                                    {{ $category->is_active ? 'مفعّل' : 'معطّل' }}
                                </span>
                            </td>
                            <td class="px-4 py-3 text-center">
                                <div class="flex items-center justify-center gap-2">
                                    <a href="{{ route('finance.expense-categories.edit', $category) }}" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-emerald-400 hover:text-emerald-600 dark:border-ink-600">تعديل</a>
                                    <form method="POST" action="{{ route('finance.expense-categories.destroy', $category) }}" data-swal-confirm data-swal-title="حذف هذا التصنيف؟">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid dark:border-ink-600">حذف</button>
                                    </form>
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="6" icon="🗂️" message="لا توجد تصنيفات بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>{{ $categories->links() }}</div>
    </div>
</x-layouts.app>
