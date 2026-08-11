<x-layouts.app title="تسجيل مصروف">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تسجيل مصروف</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">يُحفظ كمسودة، ثم يُرفع للاعتماد.</p>
        </div>

        @include('tenant.finance.expenses._form', [
            'expense' => $expense,
            'action' => $action,
            'method' => $method,
            'categories' => $categories,
            'employees' => $employees,
        ])
    </div>
</x-layouts.app>
