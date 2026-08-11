<?php

namespace App\Listeners\Tenancy;

use App\Events\Tenancy\JobApplicationSubmitted;
use App\Notifications\Tenant\NewJobApplicationNotification;
use App\Services\Tenancy\TenantOwnerNotifier;

class NotifyOwnersOfJobApplicationSubmitted
{
    public function __construct(private readonly TenantOwnerNotifier $notifier) {}

    public function handle(JobApplicationSubmitted $event): void
    {
        $this->notifier->send(
            (int) $event->application->tenant_id,
            new NewJobApplicationNotification($event->application),
        );
    }
}
