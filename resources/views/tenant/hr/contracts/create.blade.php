<x-layouts.app title="إضافة عقد">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إضافة عقد</h1>
            <p class="mt-1 text-sm text-mist-500">سجّل عقد توظيف جديد لموظف في المؤسسة.</p>
        </div>
        @include('tenant.hr.contracts._form', [
            'action' => route('hr.contracts.store'),
            'method' => 'POST',
            'contract' => $contract,
            'employees' => $employees,
            'types' => $types,
            'statuses' => $statuses,
        ])
    </div>
</x-layouts.app>
