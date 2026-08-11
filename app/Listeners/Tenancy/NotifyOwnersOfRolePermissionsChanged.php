<?php

namespace App\Listeners\Tenancy;

use App\Events\Tenancy\RolePermissionsChanged;
use App\Notifications\Tenant\RolePermissionsChangedNotification;
use App\Services\Tenancy\TenantOwnerNotifier;

class NotifyOwnersOfRolePermissionsChanged
{
    public function __construct(private readonly TenantOwnerNotifier $notifier) {}

    public function handle(RolePermissionsChanged $event): void
    {
        // Always include the acting Owner — security alerts must reach every Owner.
        $this->notifier->send(
            $event->tenantId,
            new RolePermissionsChangedNotification($event->roleName, $event->action),
        );
    }
}
