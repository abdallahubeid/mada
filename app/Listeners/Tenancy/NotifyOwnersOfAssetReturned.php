<?php

namespace App\Listeners\Tenancy;

use App\Events\Tenancy\AssetReturned;
use App\Notifications\Tenant\AssetReturnedNotification;
use App\Services\Tenancy\TenantOwnerNotifier;

class NotifyOwnersOfAssetReturned
{
    public function __construct(private readonly TenantOwnerNotifier $notifier) {}

    public function handle(AssetReturned $event): void
    {
        $this->notifier->send(
            (int) $event->asset->tenant_id,
            new AssetReturnedNotification($event->asset, $event->assignment, $event->nextStatus),
        );
    }
}
