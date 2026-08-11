<?php

namespace App\Listeners\Tenancy;

use App\Events\Tenancy\ApplicantAccepted;
use App\Events\Tenancy\AssetAssigned;
use App\Events\Tenancy\AttendanceMarkedLate;
use App\Events\Tenancy\ContractExpiringSoon;
use App\Events\Tenancy\ContractLifecycleChanged;
use App\Events\Tenancy\EmployeeCreated;
use App\Events\Tenancy\SubscriptionLimitApproaching;
use App\Events\Tenancy\SubscriptionLimitReached;
use App\Events\Tenancy\SubscriptionRenewalDue;
use App\Events\Tenancy\TeamMemberCreated;
use App\Events\Tenancy\TeamMemberDeactivated;
use App\Events\Tenancy\UrgentAnnouncementPublished;
use App\Notifications\Tenant\ApplicantAcceptedNotification;
use App\Notifications\Tenant\AssetAssignedNotification;
use App\Notifications\Tenant\AttendanceMarkedLateNotification;
use App\Notifications\Tenant\ContractLifecycleNotification;
use App\Notifications\Tenant\EmployeeCreatedNotification;
use App\Notifications\Tenant\SubscriptionLimitNotification;
use App\Notifications\Tenant\TeamMemberAccessNotification;
use App\Notifications\Tenant\UrgentAnnouncementPublishedNotification;
use App\Services\Tenancy\TenantOwnerNotifier;
use Illuminate\Events\Dispatcher;

/**
 * Registers Owner notification listeners for Waves 2–4.
 */
class NotifyOwnersOfWaveEvents
{
    public function __construct(private readonly TenantOwnerNotifier $notifier) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(EmployeeCreated::class, [self::class, 'employeeCreated']);
        $events->listen(AssetAssigned::class, [self::class, 'assetAssigned']);
        $events->listen(ApplicantAccepted::class, [self::class, 'applicantAccepted']);
        $events->listen(AttendanceMarkedLate::class, [self::class, 'attendanceLate']);
        $events->listen(UrgentAnnouncementPublished::class, [self::class, 'urgentAnnouncement']);
        $events->listen(TeamMemberCreated::class, [self::class, 'teamCreated']);
        $events->listen(TeamMemberDeactivated::class, [self::class, 'teamDeactivated']);
        $events->listen(ContractLifecycleChanged::class, [self::class, 'contractLifecycle']);
        $events->listen(ContractExpiringSoon::class, [self::class, 'contractExpiring']);
        $events->listen(SubscriptionLimitApproaching::class, [self::class, 'limitApproaching']);
        $events->listen(SubscriptionLimitReached::class, [self::class, 'limitReached']);
        $events->listen(SubscriptionRenewalDue::class, [self::class, 'renewalDue']);
    }

    public function employeeCreated(EmployeeCreated $event): void
    {
        $this->notifier->send(
            (int) $event->employee->tenant_id,
            new EmployeeCreatedNotification($event->employee),
        );
    }

    public function assetAssigned(AssetAssigned $event): void
    {
        $this->notifier->send(
            (int) $event->asset->tenant_id,
            new AssetAssignedNotification($event->asset, $event->assignment),
        );
    }

    public function applicantAccepted(ApplicantAccepted $event): void
    {
        $this->notifier->send(
            (int) $event->application->tenant_id,
            new ApplicantAcceptedNotification($event->application, $event->employee),
        );
    }

    public function attendanceLate(AttendanceMarkedLate $event): void
    {
        $this->notifier->send(
            (int) $event->attendance->tenant_id,
            new AttendanceMarkedLateNotification($event->attendance),
        );
    }

    public function urgentAnnouncement(UrgentAnnouncementPublished $event): void
    {
        $this->notifier->send(
            (int) $event->announcement->tenant_id,
            new UrgentAnnouncementPublishedNotification($event->announcement),
        );
    }

    public function teamCreated(TeamMemberCreated $event): void
    {
        $this->notifier->send(
            (int) $event->member->tenant_id,
            new TeamMemberAccessNotification($event->member, 'created', $event->roleName),
        );
    }

    public function teamDeactivated(TeamMemberDeactivated $event): void
    {
        $this->notifier->send(
            (int) $event->member->tenant_id,
            new TeamMemberAccessNotification($event->member, $event->action),
        );
    }

    public function contractLifecycle(ContractLifecycleChanged $event): void
    {
        $this->notifier->send(
            (int) $event->contract->tenant_id,
            new ContractLifecycleNotification($event->contract, $event->action),
        );
    }

    public function contractExpiring(ContractExpiringSoon $event): void
    {
        $this->notifier->send(
            (int) $event->contract->tenant_id,
            new ContractLifecycleNotification($event->contract, 'expiring'),
        );
    }

    public function limitApproaching(SubscriptionLimitApproaching $event): void
    {
        $this->notifier->send(
            (int) $event->tenant->id,
            new SubscriptionLimitNotification(
                'approaching',
                $event->label,
                $event->used,
                $event->limit,
                $event->percent,
            ),
        );
    }

    public function limitReached(SubscriptionLimitReached $event): void
    {
        $this->notifier->send(
            (int) $event->tenant->id,
            new SubscriptionLimitNotification(
                'reached',
                $event->label,
                $event->used,
                $event->limit,
            ),
        );
    }

    public function renewalDue(SubscriptionRenewalDue $event): void
    {
        $this->notifier->send(
            (int) $event->tenant->id,
            new SubscriptionLimitNotification(
                'renewal',
                daysRemaining: $event->daysRemaining,
            ),
        );
    }
}
