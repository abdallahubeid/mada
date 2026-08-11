<?php

namespace App\Domain\Messaging;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Models\User;
use Illuminate\Support\Collection;

/**
 * Who a given employee is allowed to start a conversation with.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE THREE FILTERS, AND WHY EACH IS HERE
 *
 * 1. SAME TENANT. Enforced twice over: `Employee` carries the global tenant
 *    scope, and the join to `users` is constrained on `tenant_id` explicitly.
 *    The duplication is deliberate — a global scope protects the model it is
 *    attached to, and this query reaches through a join to a model that has
 *    no such scope (`User` is not tenant-scoped; it is how a platform
 *    operator exists at all). Relying on the scope alone would leave the
 *    joined side unfiltered.
 *
 * 2. HAS A LOGIN. `employees.user_id` is nullable, and a chat participant must
 *    be a user: only a user can authenticate onto a private channel or be
 *    notified. Per the agreed decision these are HIDDEN outright rather than
 *    listed and disabled.
 *
 * 3. STILL EMPLOYED. `EmployeeStatus::Resigned` is excluded. `OnLeave` is NOT
 *    — someone on leave is still a colleague and still reachable, and
 *    filtering them out would silently break running conversations the moment
 *    a leave request was approved. `Suspended` is excluded: a suspended
 *    employee should not be receiving internal correspondence.
 *
 * Also excluded: the caller themselves. A self-conversation is not a feature,
 * and allowing one would produce a "direct" thread with a single participant
 * that the pair-hash logic treats as a legitimate pairing.
 * ─────────────────────────────────────────────────────────────────────────
 */
class EmployeeDirectory
{
    /**
     * Statuses whose holders remain contactable.
     *
     * @var list<EmployeeStatus>
     */
    private const CONTACTABLE = [
        EmployeeStatus::Active,
        EmployeeStatus::OnLeave,
    ];

    /**
     * @return Collection<int, array{user_id: int, name: string, job_title: ?string, department: ?string, initial: string}>
     */
    public function for(User $viewer, ?string $search = null, int $limit = 50): Collection
    {
        if ($viewer->tenant_id === null) {
            // A platform operator has no colleagues — they are not inside any
            // tenant, and must never be offered one's staff directory.
            return collect();
        }

        $search = trim((string) $search);

        return Employee::query()
            ->join('users', 'users.id', '=', 'employees.user_id')
            ->leftJoin('departments', 'departments.id', '=', 'employees.department_id')
            ->whereNotNull('employees.user_id')
            ->where('users.tenant_id', $viewer->tenant_id)
            ->where('users.id', '!=', $viewer->id)
            ->whereNull('users.deleted_at')
            ->where('users.is_active', true)
            ->whereIn('employees.status', array_map(
                fn (EmployeeStatus $status): string => $status->value,
                self::CONTACTABLE,
            ))
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(function ($inner) use ($like): void {
                    $inner->where('users.name', 'like', $like)
                        ->orWhere('employees.job_title', 'like', $like)
                        ->orWhere('departments.name', 'like', $like);
                });
            })
            ->orderBy('users.name')
            ->limit($limit)
            ->get([
                'users.id as user_id',
                'users.name as name',
                'employees.job_title as job_title',
                'departments.name as department',
            ])
            ->map(fn ($row): array => [
                'user_id' => (int) $row->user_id,
                'name' => (string) $row->name,
                'job_title' => $row->job_title,
                'department' => $row->department,
                'initial' => mb_substr((string) $row->name, 0, 1),
            ]);
    }

    /**
     * Whether the viewer may open a conversation with this specific user.
     *
     * The directory renders a list; this answers the same question for a
     * single id arriving from a request body, where the client could have sent
     * anything. Both must agree, so this is expressed in terms of the same
     * query rather than re-deriving the rules.
     */
    public function canReach(User $viewer, int $targetUserId): bool
    {
        if ($viewer->tenant_id === null || $viewer->id === $targetUserId) {
            return false;
        }

        return $this->for($viewer, null, PHP_INT_MAX)
            ->contains(fn (array $row): bool => $row['user_id'] === $targetUserId);
    }
}
