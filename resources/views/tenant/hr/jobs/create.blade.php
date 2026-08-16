<x-layouts.app title="إضافة وظيفة">
    <div class="mx-auto max-w-3xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إضافة وظيفة</h1>
            <p class="mt-1 text-sm text-mist-500">أنشئ شاغراً داخلياً أو للنشر على بوابة التوظيف.</p>
        </div>
        @include('tenant.hr.jobs._form', [
            'action' => route('hr.jobs.store'),
            'method' => 'POST',
            'job' => $job,
            'departments' => $departments,
            'employmentTypes' => $employmentTypes,
            'statuses' => $statuses,
        ])
    </div>
</x-layouts.app>
