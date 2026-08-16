<x-layouts.app title="التقارير والتصدير">
    <div class="mx-auto max-w-5xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">التقارير والتصدير</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">صدّر ملخصات الحضور والإجازات وكشف الموظفين وسجل النشاط بصيغة Excel (CSV) أو للطباعة/PDF.</p>
        </div>

        <div class="grid gap-4 md:grid-cols-2 xl:grid-cols-4">
            <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">ملخص الحضور</h2>
                <p class="mt-1 text-sm text-mist-500">تقرير يومي للحضور والانصراف خلال فترة محددة.</p>
                <form method="GET" action="{{ route('tenant.reports.attendance') }}" class="mt-4 space-y-3">
                    <input type="date" name="from" value="{{ now()->startOfMonth()->toDateString() }}" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                    <input type="date" name="to" value="{{ now()->toDateString() }}" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                    <div class="flex gap-2">
                        <button type="submit" name="format" value="csv" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white hover:bg-brand-600">Excel / CSV</button>
                        <button type="submit" name="format" value="pdf" class="flex-1 rounded-xl border border-mist-200 py-2 text-sm font-semibold text-ink-700 dark:border-ink-600 dark:text-mist-200">PDF</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">طلبات الإجازة</h2>
                <p class="mt-1 text-sm text-mist-500">تصدير طلبات الإجازة مع الحالة والنوع.</p>
                <form method="GET" action="{{ route('tenant.reports.leaves') }}" class="mt-4 space-y-3">
                    <select name="status" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <option value="all">كل الحالات</option>
                        <option value="pending">معلّق</option>
                        <option value="approved">معتمد</option>
                        <option value="rejected">مرفوض</option>
                    </select>
                    <div class="flex gap-2">
                        <button type="submit" name="format" value="csv" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white hover:bg-brand-600">Excel / CSV</button>
                        <button type="submit" name="format" value="pdf" class="flex-1 rounded-xl border border-mist-200 py-2 text-sm font-semibold text-ink-700 dark:border-ink-600 dark:text-mist-200">PDF</button>
                    </div>
                </form>
            </section>

            <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">كشف الموظفين</h2>
                <p class="mt-1 text-sm text-mist-500">قائمة الموظفين مع القسم والمسمى والحالة.</p>
                <form method="GET" action="{{ route('tenant.reports.employees') }}" class="mt-4 space-y-3">
                    <p class="text-xs text-mist-400">يشمل جميع الموظفين في المؤسسة الحالية.</p>
                    <div class="flex gap-2">
                        <button type="submit" name="format" value="csv" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white hover:bg-brand-600">Excel / CSV</button>
                        <button type="submit" name="format" value="pdf" class="flex-1 rounded-xl border border-mist-200 py-2 text-sm font-semibold text-ink-700 dark:border-ink-600 dark:text-mist-200">PDF</button>
                    </div>
                </form>
            </section>

            @can('tenant.audit_logs.view')
                <section class="rounded-2xl border border-mist-200 bg-white p-4 shadow-sm dark:border-ink-600 dark:bg-ink-800">
                    <h2 class="font-display text-lg font-medium text-ink-900 dark:text-ink-50">سجل النشاط والأمان</h2>
                    <p class="mt-1 text-sm text-mist-500">تصدير سجل المراجعة بصيغة مفهومة للإدارة مع فلترة بالتاريخ والوحدة.</p>
                    <form method="GET" action="{{ route('tenant.reports.audit-logs') }}" class="mt-4 space-y-3">
                        <input type="date" name="from" value="{{ now()->startOfMonth()->toDateString() }}" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <input type="date" name="to" value="{{ now()->toDateString() }}" dir="ltr" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                        <select name="module" class="w-full rounded-xl border border-mist-200 px-3 py-2 text-sm dark:border-ink-600 dark:bg-ink-900">
                            <option value="all">كل الوحدات</option>
                            @foreach ($auditModules as $moduleKey => $moduleLabel)
                                <option value="{{ $moduleKey }}">{{ $moduleLabel }}</option>
                            @endforeach
                        </select>
                        <div class="flex gap-2">
                            <button type="submit" name="format" value="csv" class="flex-1 rounded-xl bg-brand-500 py-2 text-sm font-semibold text-white hover:bg-brand-600">Excel / CSV</button>
                            <button type="submit" name="format" value="pdf" class="flex-1 rounded-xl border border-mist-200 py-2 text-sm font-semibold text-ink-700 dark:border-ink-600 dark:text-mist-200">PDF</button>
                        </div>
                    </form>
                </section>
            @endcan
        </div>
    </div>
</x-layouts.app>
