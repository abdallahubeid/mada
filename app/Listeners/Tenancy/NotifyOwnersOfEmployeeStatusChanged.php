<?php

namespace App\Listeners\Tenancy;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Events\Tenancy\EmployeeStatusChanged;
use App\Notifications\Tenant\EmployeeStatusChangedNotification;
use App\Services\Tenancy\TenantOwnerNotifier;

class NotifyOwnersOfEmployeeStatusChanged
{
    public function __construct(private readonly TenantOwnerNotifier $notifier) {}

    public function handle(EmployeeStatusChanged $event): void
    {
        if (! $event->deleted) {
            $notifiableStatuses = [EmployeeStatus::Resigned, EmployeeStatus::Suspended];

            if ($event->newStatus === null || ! in_array($event->newStatus, $notifiableStatuses, true)) {
                return;
            }

            if ($event->previousStatus === $event->newStatus) {
                return;
            }
        }

        $this->notifier->send(
            (int) $event->employee->tenant_id,
            new EmployeeStatusChangedNotification(
                $event->employee,
                $event->previousStatus,
                $event->newStatus,
                $event->deleted,
            ),
        );
    }
}
