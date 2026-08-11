<x-layouts.app title="تعديل بند">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تعديل بند</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">
                التعديل يؤثر على المسيرات الجديدة فقط — البنود المسجّلة على قسائم سابقة تحتفظ بقيمها المجمّدة.
            </p>
        </div>

        @include('tenant.finance.line-item-types._form', [
            'type' => $type,
            'action' => $action,
            'method' => $method,
            'kinds' => $kinds,
        ])
    </div>
</x-layouts.app>
