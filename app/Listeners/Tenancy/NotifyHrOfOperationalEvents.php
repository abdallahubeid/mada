<?php

namespace App\Listeners\Tenancy;

use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Events\Tenancy\ApplicantAccepted;
use App\Events\Tenancy\AssetAssigned;
use App\Events\Tenancy\AttendanceMarkedLate;
use App\Events\Tenancy\ContractExpiringSoon;
use App\Events\Tenancy\ContractLifecycleChanged;
use App\Events\Tenancy\EmployeeCreated;
use App\Events\Tenancy\JobApplicationSubmitted;
use App\Events\Tenancy\LeaveRequestSubmitted;
use App\Events\Tenancy\UrgentAnnouncementPublished;
use App\Notifications\Tenant\ApplicantAcceptedNotification;
use App\Notifications\Tenant\AssetAssignedNotification;
use App\Notifications\Tenant\AttendanceMarkedLateNotification;
use App\Notifications\Tenant\ContractLifecycleNotification;
use App\Notifications\Tenant\EmployeeCreatedNotification;
use App\Notifications\Tenant\NewJobApplicationNotification;
use App\Notifications\Tenant\NewLeaveRequestNotification;
use App\Notifications\Tenant\UrgentAnnouncementPublishedNotification;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Events\Dispatcher;

/**
 * Fans the existing operational events out to the staff who actually act on
 * them, alongside the Owner-scoped delivery in {@see NotifyOwnersOfWaveEvents}
 * and {@see NotifyOwnersOfLeaveRequestSubmitted}.
 *
 * Before this listener existed, an HR Manager received nothing at all — not
 * even the leave requests they are responsible for approving.
 *
 * Every handler passes `includeOwners: false`, because the Owner listeners
 * already deliver these same notifications. Without it, a user holding both
 * Owner and HR Manager would receive each notification twice.
 *
 * Recipients are resolved by *permission*, not role name, so custom tenant
 * roles that hold the relevant ability are included automatically.
 */
class NotifyHrOfOperationalEvents
{
    public function __construct(private readonly TenantNotifier $notifier) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(LeaveRequestSubmitted::class, [self::class, 'leaveSubmitted']);
        $events->listen(AttendanceMarkedLate::class, [self::class, 'attendanceLate']);
        $events->listen(EmployeeCreated::class, [self::class, 'employeeCreated']);
        $events->listen(JobApplicationSubmitted::class, [self::class, 'jobApplication']);
        $events->listen(ApplicantAccepted::class, [self::class, 'applicantAccepted']);
        $events->listen(ContractLifecycleChanged::class, [self::class, 'contractLifecycle']);
        $events->listen(ContractExpiringSoon::class, [self::class, 'contractExpiring']);
        $events->listen(AssetAssigned::class, [self::class, 'assetAssigned']);
        $events->listen(UrgentAnnouncementPublished::class, [self::class, 'urgentAnnouncement']);
    }

    public function leaveSubmitted(LeaveRequestSubmitted $event): void
    {
        $leaveRequest = $event->leaveRequest;

        if ($leaveRequest->status !== LeaveRequestStatus::Pending) {
            return;
        }

        $this->notifier->toPermission(
            (int) $leaveRequest->tenant_id,
            'hr.leaves.approve',
            new NewLeaveRequestNotification($leaveRequest),
            $event->actorUserId,
            includeOwners: false,
        );

        // The requester's own line manager, who may hold no HR permission at all
        // but is the first approval level under manager escalation.
        $this->notifier->toLineManagerOf(
            $leaveRequest->employee,
            new NewLeaveRequestNotification($leaveRequest),
        );
    }

    public function attendanceLate(AttendanceMarkedLate $event): void
    {
        $this->notifier->toPermission(
            (int) $event->attendance->tenant_id,
            'hr.attendance.view_any',
            new AttendanceMarkedLateNotification($event->attendance),
            includeOwners: false,
        );
    }

    public function employeeCreated(EmployeeCreated $event): void
    {
        $this->notifier->toPermission(
            (int) $event->employee->tenant_id,
            'hr.employees.view_any',
            new EmployeeCreatedNotification($event->employee),
            includeOwners: false,
        );
    }

    public function jobApplication(JobApplicationSubmitted $event): void
    {
        $this->notifier->toPermission(
            (int) $event->application->tenant_id,
            'hr.applications.view_any',
            new NewJobApplicationNotification($event->application),
            includeOwners: false,
        );
    }

    public function applicantAccepted(ApplicantAccepted $event): void
    {
        $this->notifier->toPermission(
            (int) $event->application->tenant_id,
            'hr.applications.view_any',
            new ApplicantAcceptedNotification($event->application, $event->employee),
            includeOwners: false,
        );
    }

    public function contractLifecycle(ContractLifecycleChanged $event): void
    {
        $this->notifier->toPermission(
            (int) $event->contract->tenant_id,
            'hr.contracts.view_any',
            new ContractLifecycleNotification($event->contract, $event->action),
            includeOwners: false,
        );
    }

    public function contractExpiring(ContractExpiringSoon $event): void
    {
        $this->notifier->toPermission(
            (int) $event->contract->tenant_id,
            'hr.contracts.view_any',
            new ContractLifecycleNotification($event->contract, 'expiring'),
            includeOwners: false,
        );
    }

    /**
     * The employee receiving custody needs to know about it — until now this
     * only ever reached Owners.
     */
    public function assetAssigned(AssetAssigned $event): void
    {
        $this->notifier->toPermission(
            (int) $event->asset->tenant_id,
            'hr.assets.view_any',
            new AssetAssignedNotification($event->asset, $event->assignment),
            includeOwners: false,
        );

        $this->notifier->toEmployee(
            $event->assignment->employee,
            new AssetAssignedNotification($event->asset, $event->assignment),
        );
    }

    /**
     * Urgent announcements are the one genuinely company-wide event: an
     * announcement staff cannot see is not doing its job.
     */
    public function urgentAnnouncement(UrgentAnnouncementPublished $event): void
    {
        $owners = $this->notifier->owners((int) $event->announcement->tenant_id)->modelKeys();

        $staff = $this->notifier
            ->allStaff((int) $event->announcement->tenant_id)
            ->whereNotIn('id', $owners)
            ->values();

        $this->notifier->toUsers(
            $staff,
            new UrgentAnnouncementPublishedNotification($event->announcement),
        );
    }
}
