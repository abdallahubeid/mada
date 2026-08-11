<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Events\Tenancy\TeamMemberCreated;
use App\Events\Tenancy\TeamMemberDeactivated;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTeamMemberRequest;
use App\Http\Requests\Tenant\UpdateTeamMemberRequest;
use App\Mail\Tenancy\EmployeeWelcomeMail;
use App\Models\User;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Spatie\Permission\Models\Role;

class TeamController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $tenantId = $this->tenantContext->getTenantId();

        $members = User::query()
            ->where('tenant_id', $tenantId)
            ->with(['roles', 'department'])
            ->when($request->filled('q'), function ($query) use ($request): void {
                $q = '%'.mb_strtolower((string) $request->string('q')).'%';
                $query->where(function ($inner) use ($q): void {
                    $inner->whereRaw('LOWER(name) like ?', [$q])
                        ->orWhereRaw('LOWER(email) like ?', [$q]);
                });
            })
            ->when(
                $request->filled('department_id') && $request->string('department_id') !== 'all',
                fn ($query) => $query->where('department_id', (int) $request->integer('department_id')),
            )
            ->orderBy('name')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.team.index', [
            'members' => $members,
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'filters' => [
                'q' => (string) $request->string('q'),
                'department_id' => (string) $request->string('department_id', 'all'),
            ],
            'roleLabels' => TenantPermissionCatalog::roleLabels(),
        ]);
    }

    public function create(): View
    {
        return view('tenant.team.create', $this->formShared() + [
            'member' => new User,
            'directPermissions' => [],
        ]);
    }

    public function store(StoreTeamMemberRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        abort_unless($tenant !== null, 403);

        $data = $request->validated();
        $plainPassword = $data['auto_generate_password']
            ? Str::password(12)
            : (string) $data['password'];

        $member = DB::transaction(function () use ($data, $plainPassword, $tenant): User {
            $member = User::query()->create([
                'tenant_id' => $tenant->id,
                'department_id' => $data['department_id'] ?? null,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $plainPassword,
                'is_active' => true,
            ]);

            $this->syncMemberAccess($member, $data['role'], $data['permissions'] ?? []);

            return $member;
        });

        $roleLabel = TenantPermissionCatalog::roleLabels()[$data['role']] ?? $data['role'];

        Mail::to($member->email)->send(new EmployeeWelcomeMail(
            $member,
            $tenant,
            $plainPassword,
            $roleLabel,
        ));

        event(new TeamMemberCreated($member, $roleLabel));

        flash()->success('تم إنشاء عضو الفريق وإرسال بيانات الدخول بالبريد.');

        return redirect()->route('team.index');
    }

    public function edit(User $user): View
    {
        $this->ensureTenantMember($user);

        $user->load(['roles', 'permissions']);

        return view('tenant.team.edit', $this->formShared() + [
            'member' => $user,
            'directPermissions' => $user->getDirectPermissions()->pluck('name')->all(),
        ]);
    }

    public function update(UpdateTeamMemberRequest $request, User $user): RedirectResponse
    {
        $this->ensureTenantMember($user);
        $tenant = $this->tenantContext->getTenant();
        abort_unless($tenant !== null, 403);

        $data = $request->validated();

        if (
            $user->hasRole(TenantPermissionCatalog::ROLE_OWNER)
            && $data['role'] !== TenantPermissionCatalog::ROLE_OWNER
            && $this->isSoleActiveOwner($user)
        ) {
            flash()->error('لا يمكن تغيير دور المالك الوحيد النشط للمؤسسة.');

            return back()->withInput();
        }

        $plainPassword = null;
        if ($data['reset_password']) {
            $plainPassword = $data['auto_generate_password']
                ? Str::password(12)
                : (string) $data['password'];
        }

        DB::transaction(function () use ($user, $data, $plainPassword): void {
            $user->fill([
                'name' => $data['name'],
                'email' => $data['email'],
                'department_id' => $data['department_id'] ?? null,
            ]);

            if ($plainPassword !== null) {
                $user->password = $plainPassword;
            }

            $user->save();
            $this->syncMemberAccess($user, $data['role'], $data['permissions'] ?? []);
        });

        if ($plainPassword !== null) {
            $roleLabel = TenantPermissionCatalog::roleLabels()[$data['role']] ?? $data['role'];
            Mail::to($user->email)->send(new EmployeeWelcomeMail(
                $user->fresh(),
                $tenant,
                $plainPassword,
                $roleLabel,
            ));
        }

        flash()->info('تم تحديث بيانات عضو الفريق بنجاح.');

        return redirect()->route('team.index');
    }

    public function destroy(User $user): RedirectResponse
    {
        $this->ensureTenantMember($user);

        if ($user->id === auth()->id()) {
            flash()->error('لا يمكنك حذف حسابك الحالي.');

            return back();
        }

        if ($this->isSoleActiveOwner($user)) {
            flash()->error('لا يمكن حذف المالك الوحيد النشط للمؤسسة.');

            return back();
        }

        event(new TeamMemberDeactivated($user, 'deleted'));

        $user->delete();

        $this->trash->flashSoftDeleted('تم حذف عضو الفريق بنجاح.', 'team-users', $user);

        return redirect()->route('team.index');
    }

    public function toggleStatus(User $user): RedirectResponse
    {
        $this->ensureTenantMember($user);

        if ($user->id === auth()->id()) {
            flash()->error('لا يمكنك تعطيل حسابك الحالي.');

            return back();
        }

        if ($user->is_active && $this->isSoleActiveOwner($user)) {
            flash()->error('لا يمكن تعطيل المالك الوحيد النشط للمؤسسة.');

            return back();
        }

        $user->forceFill(['is_active' => ! $user->is_active])->save();

        if (! $user->is_active) {
            event(new TeamMemberDeactivated($user, 'deactivated'));
        }

        flash()->info($user->is_active ? 'تم تفعيل العضو.' : 'تم تعطيل العضو.');

        return redirect()->route('team.index');
    }

    private function ensureTenantMember(User $user): void
    {
        abort_unless(
            (int) $user->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }

    /**
     * @return array<string, mixed>
     */
    private function formShared(): array
    {
        return [
            'roles' => $this->roleOptions(),
            'rolePermissionsMap' => $this->rolePermissionsMap(),
            'permissionGroups' => TenantPermissionCatalog::groups(),
            'departments' => Department::query()->orderBy('name')->pluck('name', 'id'),
            'roleLabels' => TenantPermissionCatalog::roleLabels(),
        ];
    }

    /**
     * @return Collection<string, string>
     */
    private function roleOptions()
    {
        return Role::query()
            ->where('guard_name', TenantPermissionCatalog::GUARD)
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->orderBy('name')
            ->pluck('name', 'name');
    }

    /**
     * @return array<string, list<string>>
     */
    private function rolePermissionsMap(): array
    {
        return Role::query()
            ->where('guard_name', TenantPermissionCatalog::GUARD)
            ->where('tenant_id', $this->tenantContext->getTenantId())
            ->with('permissions')
            ->orderBy('name')
            ->get()
            ->mapWithKeys(fn (Role $role): array => [
                $role->name => $role->permissions->pluck('name')->values()->all(),
            ])
            ->all();
    }

    /**
     * @param  list<string>  $permissions
     */
    private function syncMemberAccess(User $member, string $role, array $permissions): void
    {
        TenantPermissionCatalog::syncCatalog();

        $allowed = array_values(array_intersect($permissions, TenantPermissionCatalog::all()));

        $member->syncRoles([$role]);
        $member->syncPermissions($allowed);
        $member->unsetRelation('roles')->unsetRelation('permissions');
    }

    private function isSoleActiveOwner(User $user): bool
    {
        if (! $user->hasRole(TenantPermissionCatalog::ROLE_OWNER)) {
            return false;
        }

        $otherOwners = User::query()
            ->where('tenant_id', $user->tenant_id)
            ->where('is_active', true)
            ->whereKeyNot($user->id)
            ->get()
            ->filter(fn (User $member): bool => $member->hasRole(TenantPermissionCatalog::ROLE_OWNER));

        return $otherOwners->isEmpty();
    }
}
