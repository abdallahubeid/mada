<x-layouts.app title="إنشاء دور">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h2 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إنشاء دور</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">عرّف دوراً مخصصاً وحدد صلاحياته.</p>
        </div>

        @include('tenant.roles._form', [
            'action' => route('roles.store'),
            'method' => 'POST',
            'role' => $role,
            'groups' => $groups,
            'assigned' => old('permissions', []),
            'isProtected' => false,
        ])
    </div>
</x-layouts.app>
