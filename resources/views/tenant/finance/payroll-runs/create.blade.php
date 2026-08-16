<x-layouts.app title="إنشاء مسيرة رواتب">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إنشاء مسيرة رواتب</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                تُبنى المسودة من سجل العمل المُسوّى والعقود النشطة، ثم تُراجع قبل رفعها للاعتماد.
            </p>
        </div>

        @include('tenant.finance.payroll-runs._form', [
            'run' => $run,
            'action' => $action,
            'method' => $method,
        ])
    </div>
</x-layouts.app>
