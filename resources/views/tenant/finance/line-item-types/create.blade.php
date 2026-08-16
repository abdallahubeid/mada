<x-layouts.app title="إضافة بند">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إضافة بند</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">عرّف بدلاً أو استقطاعاً يُطبَّق على قسائم الرواتب.</p>
        </div>

        @include('tenant.finance.line-item-types._form', [
            'type' => $type,
            'action' => $action,
            'method' => $method,
            'kinds' => $kinds,
        ])
    </div>
</x-layouts.app>
