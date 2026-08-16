<x-layouts.app title="إضافة عضو">
    <div class="mx-auto max-w-4xl space-y-6">
        <div>
            <h1 class="font-display text-2xl font-medium text-ink-900 dark:text-ink-50">إضافة عضو للفريق</h1>
            <p class="mt-1 text-sm text-mist-500 dark:text-mist-400">أنشئ حساب مستخدم داخل المؤسسة مع الدور والصلاحيات المباشرة.</p>
        </div>

        @include('tenant.team._form', [
            'action' => route('team.store'),
            'method' => 'POST',
            'member' => $member,
            'roles' => $roles,
            'rolePermissionsMap' => $rolePermissionsMap,
            'permissionGroups' => $permissionGroups,
            'directPermissions' => [],
            'departments' => $departments,
            'roleLabels' => $roleLabels,
        ])
    </div>
</x-layouts.app>
