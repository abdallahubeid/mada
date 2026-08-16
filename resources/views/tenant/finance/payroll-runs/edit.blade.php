<x-layouts.app title="تعديل مسيرة الرواتب">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">
                تعديل مسيرة الرواتب
                <x-ui.ltr class="text-mist-500">{{ $run->period }}</x-ui.ltr>
            </h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">تعديل الملاحظات وبنود البدلات والاستقطاعات للمسودة.</p>
        </div>

        @include('tenant.finance.payroll-runs._form', [
            'run' => $run,
            'action' => $action,
            'method' => $method,
            'lineItems' => $lineItems,
        ])
    </div>
</x-layouts.app>
