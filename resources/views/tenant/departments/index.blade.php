<x-layouts.app title="الأقسام">
    <div class="space-y-6">
        <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">الأقسام</h1>
                <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">الهيكل التنظيمي للمؤسسة.</p>
            </div>
            @can('hr.departments.create')
                <a
                    href="{{ route('departments.create') }}"
                    class="inline-flex items-center justify-center gap-2 rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white shadow-glow transition hover:bg-brand-600"
                >
                    إضافة قسم
                </a>
            @endcan
        </div>

        <div class="w-full overflow-x-auto rounded-2xl border border-mist-200 bg-white shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <table class="min-w-full divide-y divide-mist-100 text-sm dark:divide-ink-700">
                <thead class="bg-mist-50 dark:bg-ink-900">
                    <tr>
                        <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الاسم</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الرمز</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">القسم الأب</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الأقسام الفرعية</th>
                        <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-center">إجراءات</th>
                    </tr>
                </thead>
                <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                    @forelse ($departments as $department)
                        <tr class="transition hover:bg-mist-50/80 dark:hover:bg-ink-900/40">
                            <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                            <td class="px-3 py-2 font-medium text-ink-900 dark:text-ink-50 text-start">{{ $department->name }}</td>
                            <td class="px-3 py-2 text-mist-500 text-start"><x-ui.ltr>{{ $department->code ?? '—' }}</x-ui.ltr></td>
                            <td class="px-3 py-2 text-mist-500 text-start">{{ $department->parent?->name ?? '—' }}</td>
                            <td class="px-3 py-2 text-mist-500 text-start">{{ $department->children_count }}</td>
                            <td class="px-3 py-2 text-center">
                                <div class="flex items-center justify-end gap-2">
                                    @can('hr.departments.update')
                                        <a
                                            href="{{ route('departments.edit', $department) }}"
                                            class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold transition hover:border-brand-500 hover:text-brand-600 dark:border-ink-600 dark:hover:border-brand-500"
                                        >
                                            تعديل
                                        </a>
                                    @endcan
                                    @can('hr.departments.delete')
                                        <form
                                            method="POST"
                                            action="{{ route('departments.destroy', $department) }}"
                                            data-swal-confirm
                                            data-swal-title="حذف هذا القسم؟"
                                            data-swal-text="سيتم الحذف الناعم ويمكن استعادته لاحقاً."
                                        >
                                            @csrf
                                            @method('DELETE')
                                            <button type="submit" class="rounded-lg border border-mist-200 px-3 py-1.5 text-xs font-semibold text-danger-solid transition hover:bg-danger-solid/10 dark:border-ink-600">
                                                حذف
                                            </button>
                                        </form>
                                    @endcan
                                </div>
                            </td>
                        </tr>
                    @empty
                        <x-ui.table-empty :colspan="6" icon="building" message="لا توجد أقسام بعد." />
                    @endforelse
                </tbody>
            </table>
        </div>

        <div>
            {{ $departments->links() }}
        </div>
    </div>
</x-layouts.app>
