<x-layouts.app title="إضافة قسم">
    <div class="mx-auto max-w-2xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إضافة قسم</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">أنشئ وحدة تنظيمية جديدة داخل مؤسستك.</p>
        </div>

        @include('tenant.hr.departments._form', [
            'action' => route('hr.departments.store'),
            'method' => 'POST',
            'department' => $department,
            'parents' => $parents,
            'heads' => $heads,
        ])
    </div>
</x-layouts.app>
