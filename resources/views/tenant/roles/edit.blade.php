<x-layouts.app title="تعديل دور">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h2 class="font-display text-2xl font-bold text-ink-900 dark:text-ink-50">تعديل دور</h2>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $roleLabel }}</p>
        </div>

        @include('tenant.roles._form', [
            'action' => route('roles.update', $role),
            'method' => 'PUT',
            'role' => $role,
            'groups' => $groups,
            'assigned' => old('permissions', $assigned),
            'isProtected' => $isProtected,
            'isOwnerRole' => $isOwnerRole ?? false,
            'roleLabel' => $roleLabel,
        ])
    </div>
</x-layouts.app>
