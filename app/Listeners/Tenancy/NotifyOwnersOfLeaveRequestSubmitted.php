<?php

namespace App\Listeners\Tenancy;

use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Events\Tenancy\LeaveRequestSubmitted;
use App\Notifications\Tenant\NewLeaveRequestNotification;
use App\Services\Tenancy\TenantOwnerNotifier;

class NotifyOwnersOfLeaveRequestSubmitted
{
    public function __construct(private readonly TenantOwnerNotifier $notifier) {}

    public function handle(LeaveRequestSubmitted $event): void
    {
        $leaveRequest = $event->leaveRequest;

        if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
            return;
        }

        $this->notifier->send(
            (int) $leaveRequest->tenant_id,
            new NewLeaveRequestNotification($leaveRequest),
        );
    }
}
