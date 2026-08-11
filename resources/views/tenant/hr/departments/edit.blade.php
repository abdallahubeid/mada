<x-layouts.app title="تعديل قسم">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تعديل قسم</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $department->name }}</p>
        </div>

        @include('tenant.hr.departments._form', [
            'action' => route('hr.departments.update', $department),
            'method' => 'PUT',
            'department' => $department,
            'parents' => $parents,
            'heads' => $heads,
        ])
    </div>
</x-layouts.app>
