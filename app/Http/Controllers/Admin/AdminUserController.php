<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreAdminUserRequest;
use App\Http\Requests\Admin\UpdateAdminUserRequest;
use App\Models\User;
use App\Services\Admin\TrashManager;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Spatie\Permission\Models\Role;

/**
 * Platform operator accounts (tenant_id = null) with Spatie roles / direct permissions.
 */
class AdminUserController extends Controller
{
    public function index(): View
    {
        $admins = User::query()
            ->whereNull('tenant_id')
            ->with(['roles', 'avatar'])
            ->orderBy('name')
            ->get();

        $metrics = [
            ['label' => 'إجمالي المشرفين', 'value' => $admins->count()],
            ['label' => 'مشرف عام', 'value' => $admins->filter(fn (User $u): bool => $u->hasRole(PlatformPermissionCatalog::ROLE_SUPER_ADMIN))->count()],
            ['label' => 'أدوار أخرى', 'value' => $admins->reject(fn (User $u): bool => $u->hasRole(PlatformPermissionCatalog::ROLE_SUPER_ADMIN))->count()],
        ];

        return view('admin.admins.index', [
            'admins' => $admins,
            'metrics' => $metrics,
            'roleLabels' => PlatformPermissionCatalog::roleLabels(),
        ]);
    }

    public function create(): View
    {
        return view('admin.admins.create', $this->formShared());
    }

    public function store(StoreAdminUserRequest $request, PlatformAuditor $auditor): RedirectResponse
    {
        $data = $request->validated();

        $admin = DB::transaction(function () use ($data): User {
            $admin = User::query()->create([
                'tenant_id' => null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
                'email_verified_at' => now(),
            ]);

            $admin->syncRoles([$data['role']]);
            $admin->syncPermissions($data['permissions'] ?? []);
            $admin->unsetRelation('roles')->unsetRelation('permissions');

            return $admin;
        });

        PlatformPermissionCatalog::forgetCachedPermissions();

        $auditor->log('admin.created', $admin);

        flash()->success('تم إنشاء المشرف.');

        return redirect()->route('admin.admins');
    }

    public function edit(User $admin): View
    {
        abort_unless($admin->tenant_id === null, 404);

        return view('admin.admins.edit', [
            ...$this->formShared(),
            'admin' => $admin->load(['roles', 'permissions']),
            'assignedRole' => $admin->roles->first()?->name,
            'directPermissions' => $admin->getDirectPermissions()->pluck('name')->all(),
        ]);
    }

    public function update(UpdateAdminUserRequest $request, User $admin, PlatformAuditor $auditor): RedirectResponse
    {
        abort_unless($admin->tenant_id === null, 404);

        $data = $request->validated();

        if (
            $admin->hasRole(PlatformPermissionCatalog::ROLE_SUPER_ADMIN)
            && ($data['role'] ?? null) !== PlatformPermissionCatalog::ROLE_SUPER_ADMIN
            && $this->superAdminCount() <= 1
        ) {
            flash()->error('لا يمكن إزالة دور المشرف العام عن آخر مشرف عام.');

            return back()->withInput();
        }

        DB::transaction(function () use ($admin, $data): void {
            $payload = [
                'name' => $data['name'],
                'email' => $data['email'],
            ];

            if (! empty($data['password'])) {
                $payload['password'] = $data['password'];
            }

            $admin->update($payload);
            $admin->syncRoles([$data['role']]);
            $admin->syncPermissions($data['permissions'] ?? []);
            $admin->unsetRelation('roles')->unsetRelation('permissions');
        });

        PlatformPermissionCatalog::forgetCachedPermissions();

        $auditor->log('admin.updated', $admin);

        flash()->info('تم تحديث المشرف.');

        return redirect()->route('admin.admins');
    }

    public function destroy(User $admin, PlatformAuditor $auditor): RedirectResponse
    {
        abort_unless($admin->tenant_id === null, 404);

        if ($admin->is(auth()->user())) {
            flash()->error('لا يمكنك حذف حسابك الحالي.');

            return back();
        }

        if (
            $admin->hasRole(PlatformPermissionCatalog::ROLE_SUPER_ADMIN)
            && $this->superAdminCount() <= 1
        ) {
            flash()->error('لا يمكن حذف آخر مشرف عام.');

            return back();
        }

        $admin->delete();

        $auditor->log('admin.deleted', $admin);

        app(TrashManager::class)->flashSoftDeleted('تم حذف المشرف.', 'admins', $admin);

        return redirect()->route('admin.admins');
    }

    /**
     * @return array{
     *     roles: Collection<int, Role>,
     *     roleLabels: array<string, string>,
     *     groups: array<string, array{label: string, permissions: array<string, string>}>,
     *     rolePermissionsMap: array<string, list<string>>
     * }
     */
    private function formShared(): array
    {
        $roles = Role::query()
            ->where('guard_name', PlatformPermissionCatalog::GUARD)
            ->whereNull('tenant_id')
            ->with('permissions')
            ->orderBy('name')
            ->get();

        /** @var array<string, list<string>> $rolePermissionsMap */
        $rolePermissionsMap = $roles
            ->mapWithKeys(fn (Role $role): array => [
                $role->name => $role->permissions->pluck('name')->values()->all(),
            ])
            ->all();

        return [
            'roles' => $roles,
            'roleLabels' => PlatformPermissionCatalog::roleLabels(),
            'groups' => PlatformPermissionCatalog::groups(),
            'rolePermissionsMap' => $rolePermissionsMap,
        ];
    }

    private function superAdminCount(): int
    {
        return User::query()
            ->whereNull('tenant_id')
            ->role(PlatformPermissionCatalog::ROLE_SUPER_ADMIN)
            ->count();
    }
}
