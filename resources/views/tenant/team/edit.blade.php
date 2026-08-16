<x-layouts.app title="تعديل عضو">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">تعديل عضو الفريق</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">{{ $member->name }}</p>
        </div>

        @include('tenant.team._form', [
            'action' => route('team.update', $member),
            'method' => 'PUT',
            'member' => $member,
            'roles' => $roles,
            'rolePermissionsMap' => $rolePermissionsMap,
            'permissionGroups' => $permissionGroups,
            'directPermissions' => $directPermissions,
            'departments' => $departments,
            'roleLabels' => $roleLabels,
        ])
    </div>
</x-layouts.app>
