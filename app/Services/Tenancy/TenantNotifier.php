<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Notifications\Tenant\TenantNotification;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Notification as NotificationFacade;
use Spatie\Permission\PermissionRegistrar;

/**
 * Role-aware fan-out for tenant notifications (database + Reverb).
 *
 * Replaces the Owner-only {@see TenantOwnerNotifier} as the single place that
 * decides *who* receives a {@see TenantNotification}.
 * Every resolver below is tenant-scoped and skips inactive users.
 *
 * Spatie role/permission lookups are team-scoped (ADR-03), so each resolver
 * binds the tenant as the permission team id for the duration of its query and
 * restores the previous team id in a finally block — the same discipline
 * TenantOwnerNotifier already used.
 */
class TenantNotifier
{
    /**
     * Tenant Owners.
     */
    public function toOwners(int $tenantId, Notification $notification, ?int $exceptUserId = null): void
    {
        $this->dispatch($this->owners($tenantId, $exceptUserId), $notification);
    }

    /**
     * Holders of a named tenant role (e.g. 'HR Manager').
     */
    public function toRole(int $tenantId, string $role, Notification $notification, ?int $exceptUserId = null): void
    {
        $this->dispatch($this->withRole($tenantId, $role, $exceptUserId), $notification);
    }

    /**
     * Everyone who can perform an ability — whether granted directly or via a
     * role. Use this for "whoever is responsible for X" routing so custom roles
     * are included automatically.
     *
     * Owners hold every ability through the Gate::before bypass rather than a
     * stored grant, so they are merged in explicitly. Pass
     * `includeOwners: false` when a separate Owner-scoped listener already
     * covers this event — otherwise a user who is both Owner and HR Manager
     * would receive the same notification twice.
     */
    public function toPermission(
        int $tenantId,
        string $permission,
        Notification $notification,
        ?int $exceptUserId = null,
        bool $includeOwners = true,
    ): void {
        $recipients = $this->withPermission($tenantId, $permission, $exceptUserId);
        $owners = $this->owners($tenantId, $exceptUserId);

        $recipients = $includeOwners
            ? $recipients->merge($owners)
            : $recipients->whereNotIn('id', $owners->modelKeys())->values();

        $this->dispatch($recipients, $notification);
    }

    /**
     * The user account linked to a specific employee record. No-op when the
     * employee has no linked user (an HR-only profile).
     */
    public function toEmployee(?Employee $employee, Notification $notification): void
    {
        if ($employee?->user_id === null) {
            return;
        }

        $this->dispatch($this->activeUsers($employee->tenant_id)->where('id', $employee->user_id), $notification);
    }

    /**
     * The user account of the employee's direct line manager.
     */
    public function toLineManagerOf(?Employee $employee, Notification $notification): void
    {
        if ($employee?->manager_id === null) {
            return;
        }

        $manager = Employee::query()->whereKey($employee->manager_id)->first();

        $this->toEmployee($manager, $notification);
    }

    /**
     * Every active user in the tenant — reserve for genuinely company-wide
     * events (urgent announcements), never routine operational noise.
     */
    public function toAllStaff(int $tenantId, Notification $notification, ?int $exceptUserId = null): void
    {
        $this->dispatch($this->activeUsers($tenantId, $exceptUserId), $notification);
    }

    /**
     * @param  Collection<int, User>  $users
     */
    public function toUsers(Collection $users, Notification $notification): void
    {
        $this->dispatch($users, $notification);
    }

    /**
     * @return Collection<int, User>
     */
    public function owners(int $tenantId, ?int $exceptUserId = null): Collection
    {
        return $this->withRole($tenantId, TenantPermissionCatalog::ROLE_OWNER, $exceptUserId);
    }

    /**
     * @return Collection<int, User>
     */
    public function withRole(int $tenantId, string $role, ?int $exceptUserId = null): Collection
    {
        return $this->withTeam($tenantId, function () use ($tenantId, $role, $exceptUserId): Collection {
            $users = User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
                ->role($role)
                ->get();

            $users->each(fn (User $user) => $user->unsetRelation('roles'));

            return $users;
        });
    }

    /**
     * @return Collection<int, User>
     */
    public function withPermission(int $tenantId, string $permission, ?int $exceptUserId = null): Collection
    {
        return $this->withTeam($tenantId, function () use ($tenantId, $permission, $exceptUserId): Collection {
            $users = User::query()
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
                ->permission($permission)
                ->get();

            // hasPermissionTo()/permission() cache the roles relation against the
            // team id active at query time. Drop it so any later team-scoped
            // check on these instances re-queries (see User::isTenantOwner()).
            $users->each(fn (User $user) => $user->unsetRelation('roles'));

            return $users;
        });
    }

    /**
     * Every active user in the tenant, regardless of role.
     *
     * @return Collection<int, User>
     */
    public function allStaff(int $tenantId, ?int $exceptUserId = null): Collection
    {
        return $this->activeUsers($tenantId, $exceptUserId);
    }

    /**
     * @return Collection<int, User>
     */
    private function activeUsers(int $tenantId, ?int $exceptUserId = null): Collection
    {
        return User::query()
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->when($exceptUserId !== null, fn ($query) => $query->whereKeyNot($exceptUserId))
            ->get();
    }

    /**
     * @param  Collection<int, User>  $recipients
     */
    private function dispatch(Collection $recipients, Notification $notification): void
    {
        $unique = $recipients->unique('id')->values();

        if ($unique->isEmpty()) {
            return;
        }

        NotificationFacade::send($unique, $notification);
    }

    /**
     * @template TReturn
     *
     * @param  callable(): TReturn  $callback
     * @return TReturn
     */
    private function withTeam(int $tenantId, callable $callback): mixed
    {
        $registrar = app(PermissionRegistrar::class);
        $previousTeamId = $registrar->getPermissionsTeamId();
        $registrar->setPermissionsTeamId($tenantId);

        try {
            return $callback();
        } finally {
            $registrar->setPermissionsTeamId($previousTeamId);
        }
    }
}
