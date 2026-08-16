<x-layouts.app title="عهدة الموظف">
    <div class="mx-auto max-w-4xl space-y-6">
        <div class="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
            <div>
                <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">عهدة {{ $employee->full_name }}</h1>
                <p class="mt-1 text-sm text-mist-500">الأصول النشطة وسجل الإسنادات السابقة.</p>
            </div>
            <div class="flex flex-wrap gap-2">
                <a href="{{ route('hr.employees.show', [$employee, 'tab' => 'assets']) }}" class="rounded-xl border border-mist-200 px-4 py-2 text-sm font-semibold dark:border-ink-600">ملف الموظف</a>
                <a href="{{ route('tenant.assets.index') }}" class="rounded-xl bg-brand-500 px-4 py-2 text-sm font-semibold text-white">كل الأصول</a>
            </div>
        </div>

        <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h2 class="font-semibold text-ink-900 dark:text-ink-50">عهدة نشطة</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-max text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs text-mist-500 dark:border-ink-700">
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الرمز</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الأصل</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">تاريخ الإسناد</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الحالة عند الإسناد</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($activeAssignments as $assignment)
                            <tr>
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration }}</td>
                                <td class="px-3 py-2 font-mono text-xs text-start"><x-ui.ltr>{{ $assignment->asset?->asset_code }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-start">{{ $assignment->asset?->name }}</td>
                                <td class="px-3 py-2 text-start"><x-ui.ltr>{{ $assignment->assigned_at?->format('Y-m-d H:i') }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-start">{{ $assignment->condition_on_assign->label() }}</td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="5" icon="archive" message="لا توجد عهدة نشطة." />
                        @endforelse
                    </tbody>
                </table>
            </div>
        </section>

        <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
            <h2 class="font-semibold text-ink-900 dark:text-ink-50">سجل سابق</h2>
            <div class="mt-3 overflow-x-auto">
                <table class="w-full min-w-max text-sm">
                    <thead>
                        <tr class="border-b border-mist-100 text-xs text-mist-500 dark:border-ink-700">
                            <th class="w-12 px-3 py-2 text-center text-xs font-medium text-mist-500 dark:text-mist-400">#</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">الأصل</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">من</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">إلى</th>
                            <th class="px-3 py-2 text-xs font-medium text-mist-500 dark:text-mist-400 text-start">عند الإعادة</th>
                        </tr>
                    </thead>
                    <tbody class="divide-y divide-mist-100 dark:divide-ink-700">
                        @forelse ($history as $assignment)
                            <tr>
                                <td class="w-12 px-3 py-2 text-center text-sm tabular-nums text-mist-500">{{ $loop->iteration + ($history->currentPage() - 1) * $history->perPage() }}</td>
                                <td class="px-3 py-2 text-start">{{ $assignment->asset?->asset_code }} — {{ $assignment->asset?->name }}</td>
                                <td class="px-3 py-2 text-start"><x-ui.ltr>{{ $assignment->assigned_at?->format('Y-m-d') }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-start"><x-ui.ltr>{{ $assignment->returned_at?->format('Y-m-d') }}</x-ui.ltr></td>
                                <td class="px-3 py-2 text-start">{{ $assignment->condition_on_return?->label() ?? '—' }}</td>
                            </tr>
                        @empty
                            <x-ui.table-empty :colspan="5" icon="document" message="لا يوجد سجل سابق." />
                        @endforelse
                    </tbody>
                </table>
            </div>
            @if ($history->hasPages())
                <div class="mt-3">{{ $history->links() }}</div>
            @endif
        </section>
    </div>
</x-layouts.app>
