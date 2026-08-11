<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Events\Tenancy\RolePermissionsChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTenantRoleRequest;
use App\Http\Requests\Tenant\UpdateTenantRoleRequest;
use App\Services\Tenancy\TenantAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Spatie\Permission\Models\Role;
use Spatie\Permission\PermissionRegistrar;

class RoleController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditor $auditor,
    ) {}

    public function index(): View
    {
        $tenantId = $this->tenantContext->getTenantId();

        $roles = Role::query()
            ->where('guard_name', TenantPermissionCatalog::GUARD)
            ->where('tenant_id', $tenantId)
            ->withCount(['permissions', 'users'])
            ->orderBy('name')
            ->get();

        return view('tenant.roles.index', [
            'roles' => $roles,
            'roleLabels' => TenantPermissionCatalog::roleLabels(),
            'protectedRoles' => TenantPermissionCatalog::roleNames(),
        ]);
    }

    public function create(): View
    {
        return view('tenant.roles.create', [
            'groups' => TenantPermissionCatalog::groups(),
            'role' => new Role,
        ]);
    }

    public function store(StoreTenantRoleRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $tenantId = $this->tenantContext->getTenantId();

        /** @var Role $role */
        $role = Role::query()->create([
            'name' => $data['name'],
            'guard_name' => TenantPermissionCatalog::GUARD,
            'tenant_id' => $tenantId,
        ]);

        $this->syncRolePermissions($role, $data['permissions'] ?? []);

        $this->auditor->log('role.created', 'rbac', null, [
            'role' => $role->name,
            'permissions' => $data['permissions'] ?? [],
        ]);

        event(new RolePermissionsChanged(
            (int) $tenantId,
            $role->name,
            'created',
            $request->user()?->id,
        ));

        flash()->success('تم إنشاء الدور بنجاح.');

        return redirect()->route('roles.index');
    }

    public function edit(Role $role): View
    {
        $this->ensureTenantRole($role);
        $role->load('permissions');

        $isOwnerRole = $role->name === TenantPermissionCatalog::ROLE_OWNER;

        return view('tenant.roles.edit', [
            'role' => $role,
            'groups' => TenantPermissionCatalog::groups(),
            // Owner always displays the full catalog — access is Gate-implicit, not pivot-bound.
            'assigned' => $isOwnerRole
                ? TenantPermissionCatalog::all()
                : $role->permissions->pluck('name')->all(),
            'isProtected' => TenantPermissionCatalog::isProtectedRole($role->name),
            'isOwnerRole' => $isOwnerRole,
            'roleLabel' => TenantPermissionCatalog::roleLabels()[$role->name] ?? $role->name,
        ]);
    }

    public function update(UpdateTenantRoleRequest $request, Role $role): RedirectResponse
    {
        $this->ensureTenantRole($role);

        $data = $request->validated();

        if (! TenantPermissionCatalog::isProtectedRole($role->name) && isset($data['name'])) {
            $role->name = $data['name'];
            $role->save();
        }

        // Owner keeps implicit full access; never let the form strip pivots / confuse ops.
        if ($role->name === TenantPermissionCatalog::ROLE_OWNER) {
            $this->syncRolePermissions($role, TenantPermissionCatalog::all());
        } else {
            $this->syncRolePermissions($role, $data['permissions'] ?? []);
        }

        $this->auditor->log('role.updated', 'rbac', null, [
            'role' => $role->name,
            'permissions' => $role->name === TenantPermissionCatalog::ROLE_OWNER
                ? TenantPermissionCatalog::all()
                : ($data['permissions'] ?? []),
        ]);

        event(new RolePermissionsChanged(
            (int) $role->tenant_id,
            $role->name,
            'updated',
            $request->user()?->id,
        ));

        flash()->info('تم تحديث الدور بنجاح.');

        return redirect()->route('roles.index');
    }

    public function destroy(Role $role): RedirectResponse
    {
        $this->ensureTenantRole($role);

        if (TenantPermissionCatalog::isProtectedRole($role->name)) {
            flash()->error('لا يمكن حذف الأدوار النظامية الافتراضية.');

            return back();
        }

        if ($role->users()->count() > 0) {
            flash()->error('لا يمكن حذف دور مرتبط بمستخدمين. انقل المستخدمين أولاً.');

            return back();
        }

        $roleName = $role->name;
        $role->syncPermissions([]);
        $role->delete();
        app(PermissionRegistrar::class)->forgetCachedPermissions();

        $this->auditor->log('role.deleted', 'rbac', null, [
            'role' => $roleName,
        ]);

        event(new RolePermissionsChanged(
            (int) $this->tenantContext->getTenantId(),
            $roleName,
            'deleted',
            request()->user()?->id,
        ));

        flash()->warning('تم حذف الدور.');

        return redirect()->route('roles.index');
    }

    private function ensureTenantRole(Role $role): void
    {
        abort_unless($role->guard_name === TenantPermissionCatalog::GUARD, 404);
        abort_unless((int) $role->tenant_id === (int) $this->tenantContext->getTenantId(), 404);
    }

    /**
     * @param  list<string>  $permissions
     */
    private function syncRolePermissions(Role $role, array $permissions): void
    {
        TenantPermissionCatalog::syncCatalog();

        $allowed = array_values(array_intersect($permissions, TenantPermissionCatalog::all()));
        $role->syncPermissions($allowed);
        app(PermissionRegistrar::class)->forgetCachedPermissions();
    }
}
