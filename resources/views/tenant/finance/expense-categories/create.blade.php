<x-layouts.app title="إضافة تصنيف">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">إضافة تصنيف مصروفات</h1>
        </div>

        @include('tenant.finance.expense-categories._form', [
            'category' => $category,
            'action' => $action,
            'method' => $method,
        ])
    </div>
</x-layouts.app>
