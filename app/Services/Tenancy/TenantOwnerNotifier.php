<?php

namespace App\Services\Tenancy;

use App\Models\User;
use Illuminate\Notifications\Notification;
use Illuminate\Support\Collection;

/**
 * Owner-only notification fan-out.
 *
 * Retained as a thin wrapper over {@see TenantNotifier} so the existing
 * Owner-scoped listeners keep working unchanged. New code should depend on
 * TenantNotifier directly and pick an explicit recipient resolver — Owner-only
 * delivery is rarely what a tenant-wide event actually wants.
 */
class TenantOwnerNotifier
{
    public function __construct(private readonly TenantNotifier $notifier) {}

    /**
     * @param  Notification|callable(): Notification  $notification
     */
    public function send(int $tenantId, Notification|callable $notification, ?int $exceptUserId = null): void
    {
        $instance = is_callable($notification) ? $notification() : $notification;

        $this->notifier->toOwners($tenantId, $instance, $exceptUserId);
    }

    /**
     * @return Collection<int, User>
     */
    public function ownersForTenant(int $tenantId, ?int $exceptUserId = null): Collection
    {
        return $this->notifier->owners($tenantId, $exceptUserId);
    }
}
