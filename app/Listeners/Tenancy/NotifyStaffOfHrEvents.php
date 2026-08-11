<?php

namespace App\Listeners\Tenancy;

use App\Domain\Tenancy\Enums\TaskStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Events\Tenancy\EvaluationPublished;
use App\Events\Tenancy\LeaveRequestDecided;
use App\Events\Tenancy\TaskAssigned;
use App\Events\Tenancy\TaskCompleted;
use App\Notifications\Tenant\EvaluationPublishedNotification;
use App\Notifications\Tenant\LeaveDecisionNotification;
use App\Notifications\Tenant\TaskAssignedNotification;
use App\Notifications\Tenant\TaskCompletedNotification;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Events\Dispatcher;

/**
 * Routes HR events to the individual staff member they concern, rather than to
 * Owners — see {@see NotifyOwnersOfWaveEvents} for the Owner-scoped set.
 *
 * Each handler targets the smallest correct audience: the assignee for their
 * own task, the assigning manager for its completion, the requester for their
 * own leave decision, the subject for their own evaluation. The acting user is
 * always excluded so nobody is notified about their own action.
 */
class NotifyStaffOfHrEvents
{
    public function __construct(private readonly TenantNotifier $notifier) {}

    public function subscribe(Dispatcher $events): void
    {
        $events->listen(TaskAssigned::class, [self::class, 'taskAssigned']);
        $events->listen(TaskCompleted::class, [self::class, 'taskCompleted']);
        $events->listen(LeaveRequestDecided::class, [self::class, 'leaveDecided']);
        $events->listen(EvaluationPublished::class, [self::class, 'evaluationPublished']);
    }

    public function taskAssigned(TaskAssigned $event): void
    {
        $employee = $event->task->employee ?? Employee::query()->find($event->task->employee_id);

        if ($employee?->user_id === $event->actorUserId) {
            return;
        }

        $this->notifier->toEmployee($employee, new TaskAssignedNotification($event->task));
    }

    public function taskCompleted(TaskCompleted $event): void
    {
        if ($event->task->status !== TaskStatus::Completed) {
            return;
        }

        $manager = $event->task->manager ?? Employee::query()->find($event->task->manager_id);

        if ($manager?->user_id === $event->actorUserId) {
            return;
        }

        $this->notifier->toEmployee($manager, new TaskCompletedNotification($event->task));
    }

    public function leaveDecided(LeaveRequestDecided $event): void
    {
        $employee = $event->leaveRequest->employee
            ?? Employee::query()->find($event->leaveRequest->employee_id);

        if ($employee?->user_id === $event->actorUserId) {
            return;
        }

        $this->notifier->toEmployee(
            $employee,
            new LeaveDecisionNotification($event->leaveRequest, $event->decision),
        );
    }

    public function evaluationPublished(EvaluationPublished $event): void
    {
        $employee = $event->evaluation->employee
            ?? Employee::query()->find($event->evaluation->employee_id);

        if ($employee?->user_id === $event->actorUserId) {
            return;
        }

        $this->notifier->toEmployee(
            $employee,
            new EvaluationPublishedNotification($event->evaluation),
        );
    }
}
