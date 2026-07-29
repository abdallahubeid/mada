<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreRoleRequest;
use App\Http\Requests\Admin\UpdateRolePermissionsRequest;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;

class RoleController extends Controller
{
    public function index(): View
    {
        $roles = Role::query()
            ->where('guard_name', PlatformPermissionCatalog::GUARD)
            ->whereNull('tenant_id')
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('admin.roles.index', [
            'roles' => $roles,
            'roleLabels' => PlatformPermissionCatalog::roleLabels(),
            'protectedRoles' => PlatformPermissionCatalog::roleNames(),
        ]);
    }

    public function create(): View
    {
        return view('admin.roles.create', [
            'groups' => PlatformPermissionCatalog::groups(),
        ]);
    }

    public function store(StoreRoleRequest $request, PlatformAuditor $auditor): RedirectResponse
    {
        $data = $request->validated();

        /** @var Role $role */
        $role = PlatformPermissionCatalog::withGlobalTeam(function () use ($data): Role {
            $role = Role::query()->create([
                'name' => $data['name'],
                'guard_name' => PlatformPermissionCatalog::GUARD,
                'tenant_id' => null,
            ]);

            $role->syncPermissions($data['permissions'] ?? []);

            return $role;
        });

        PlatformPermissionCatalog::forgetCachedPermissions();

        $auditor->log('role.created', $role, [
            'permissions' => $data['permissions'] ?? [],
        ]);

        flash()->success('تم إنشاء الدور.');

        return redirect()->route('admin.roles.index');
    }

    public function edit(Role $role): View
    {
        $this->ensurePlatformRole($role);

        $role->load('permissions');

        return view('admin.roles.edit', [
            'role' => $role,
            'roleLabel' => PlatformPermissionCatalog::roleLabels()[$role->name] ?? $role->name,
            'groups' => PlatformPermissionCatalog::groups(),
            'assigned' => $role->permissions->pluck('name')->all(),
        ]);
    }

    public function update(UpdateRolePermissionsRequest $request, Role $role, PlatformAuditor $auditor): RedirectResponse
    {
        $this->ensurePlatformRole($role);

        $permissions = $request->validated('permissions') ?? [];

        PlatformPermissionCatalog::withGlobalTeam(function () use ($role, $permissions): void {
            $role->syncPermissions($permissions);
        });

        PlatformPermissionCatalog::forgetCachedPermissions();

        $auditor->log('role.permissions_updated', $role, [
            'permissions' => $permissions,
        ]);

        flash()->info('تم تحديث صلاحيات الدور.');

        return redirect()->route('admin.roles.index');
    }

    public function destroy(Role $role, PlatformAuditor $auditor): RedirectResponse
    {
        $this->ensurePlatformRole($role);

        if (in_array($role->name, PlatformPermissionCatalog::roleNames(), true)) {
            flash()->error('لا يمكن حذف الأدوار النظامية الافتراضية.');

            return back();
        }

        if ($role->users()->count() > 0) {
            flash()->error('لا يمكن حذف دور مرتبط بمستخدمين. انقل المستخدمين أولًا.');

            return back();
        }

        $roleName = $role->name;

        PlatformPermissionCatalog::withGlobalTeam(function () use ($role): void {
            $role->syncPermissions([]);
            $role->delete();
        });

        PlatformPermissionCatalog::forgetCachedPermissions();

        $auditor->log('role.deleted', null, ['name' => $roleName]);

        flash()->warning('تم حذف الدور.');

        return redirect()->route('admin.roles.index');
    }

    private function ensurePlatformRole(Role $role): void
    {
        abort_unless($role->guard_name === PlatformPermissionCatalog::GUARD, 404);
        abort_unless($role->tenant_id === null, 404);
    }
}
