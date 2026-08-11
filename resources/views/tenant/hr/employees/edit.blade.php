<x-layouts.app title="تعديل موظف">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تعديل موظف</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $employee->full_name }}</p>
        </div>

        @include('tenant.hr.employees._form', [
            'action' => route('hr.employees.update', $employee),
            'method' => 'PUT',
            'employee' => $employee,
            'departments' => $departments,
            'managers' => $managers,
            'statuses' => $statuses,
        ])
    </div>
</x-layouts.app>
