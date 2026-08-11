<?php

use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use App\Domain\Tenancy\Enums\EvaluationStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Enums\TaskStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\Task;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Notifications\Tenant\EvaluationPublishedNotification;
use App\Notifications\Tenant\LeaveDecisionNotification;
use App\Notifications\Tenant\NewLeaveRequestNotification;
use App\Notifications\Tenant\TaskAssignedNotification;
use App\Notifications\Tenant\TaskCompletedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * Manager + direct report, both with linked user accounts.
 *
 * @return array{0: User, 1: Employee, 2: User, 3: Employee}
 */
function taskPair(int $tenantId): array
{
    $managerUser = User::factory()->create(['tenant_id' => $tenantId, 'is_active' => true]);
    $managerUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $manager = Employee::factory()->create([
        'tenant_id' => $tenantId,
        'user_id' => $managerUser->id,
    ]);

    $reportUser = User::factory()->create(['tenant_id' => $tenantId, 'is_active' => true]);
    $reportUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $report = Employee::factory()->create([
        'tenant_id' => $tenantId,
        'user_id' => $reportUser->id,
        'manager_id' => $manager->id,
    ]);

    return [$managerUser, $manager, $reportUser, $report];
}

test('assigning a task notifies the assignee and not the assigning manager', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$managerUser, , $reportUser, $report] = taskPair($owner->tenant_id);

    Notification::fake();

    $this->actingAs($managerUser)->post(route('hr.tasks.store'), [
        'employee_id' => $report->id,
        'title' => 'إعداد التقرير',
        'priority' => 'high',
    ])->assertRedirect(route('hr.tasks.index'));

    Notification::assertSentTo($reportUser, TaskAssignedNotification::class);
    Notification::assertNotSentTo($managerUser, TaskAssignedNotification::class);
});

test('completing a task notifies the assigning manager and not the employee', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$managerUser, $manager, $reportUser, $report] = taskPair($owner->tenant_id);

    $task = Task::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'manager_id' => $manager->id,
        'employee_id' => $report->id,
        'status' => TaskStatus::Review,
    ]);

    Notification::fake();

    $this->actingAs($reportUser)
        ->post(route('tenant.hr.my-tasks.status', $task), ['status' => 'completed'])
        ->assertRedirect(route('tenant.hr.my-tasks'));

    Notification::assertSentTo($managerUser, TaskCompletedNotification::class);
    Notification::assertNotSentTo($reportUser, TaskCompletedNotification::class);
});

test('re-saving an already completed task does not re-notify the manager', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$managerUser, $manager, $reportUser, $report] = taskPair($owner->tenant_id);

    $task = Task::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'manager_id' => $manager->id,
        'employee_id' => $report->id,
        'status' => TaskStatus::Completed,
    ]);

    Notification::fake();

    $this->actingAs($reportUser)
        ->post(route('tenant.hr.my-tasks.status', $task), ['status' => 'completed'])
        ->assertRedirect(route('tenant.hr.my-tasks'));

    Notification::assertNothingSentTo($managerUser);
});

test('approving a leave notifies the requesting employee', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [, , $reportUser, $report] = taskPair($owner->tenant_id);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'annual_days' => 20,
        'requires_approval' => true,
    ]);

    $leaveRequest = LeaveRequest::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'employee_id' => $report->id,
        'leave_type_id' => $leaveType->id,
        'status' => LeaveRequestStatus::Pending,
        'days_count' => 2,
    ]);

    Notification::fake();

    $this->actingAs($owner)
        ->post(route('hr.leaves.approve', $leaveRequest))
        ->assertRedirect();

    Notification::assertSentTo(
        $reportUser,
        fn (LeaveDecisionNotification $notification): bool => $notification->decision === 'approved',
    );
});

test('rejecting a leave notifies the requesting employee with the reason', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [, , $reportUser, $report] = taskPair($owner->tenant_id);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'requires_approval' => true,
    ]);

    $leaveRequest = LeaveRequest::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'employee_id' => $report->id,
        'leave_type_id' => $leaveType->id,
        'status' => LeaveRequestStatus::Pending,
    ]);

    Notification::fake();

    $this->actingAs($owner)
        ->post(route('hr.leaves.reject', $leaveRequest), ['rejection_reason' => 'ضغط العمل'])
        ->assertRedirect();

    Notification::assertSentTo(
        $reportUser,
        fn (LeaveDecisionNotification $notification): bool => $notification->decision === 'rejected',
    );
});

test('submitting an evaluation notifies the evaluated employee once', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$managerUser, $manager, $reportUser, $report] = taskPair($owner->tenant_id);

    Notification::fake();

    $payload = [
        'period_type' => 'quarterly',
        'period_key' => '2026-Q3',
        'intent' => 'submit',
        'rows' => [
            $report->id => ['employee_id' => $report->id, 'rating' => 4.5, 'notes' => 'ممتاز'],
        ],
    ];

    $this->actingAs($managerUser)->post(route('hr.evaluations.upsert'), $payload)->assertRedirect();

    Notification::assertSentToTimes($reportUser, EvaluationPublishedNotification::class, 1);

    // Re-submitting the same already-visible evaluation must not notify again.
    $this->actingAs($managerUser)->post(route('hr.evaluations.upsert'), $payload)->assertRedirect();

    Notification::assertSentToTimes($reportUser, EvaluationPublishedNotification::class, 1);
});

test('approving a draft evaluation period notifies the employee it becomes visible to', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [, , $reportUser, $report] = taskPair($owner->tenant_id);

    EmployeeEvaluation::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'employee_id' => $report->id,
        'period_type' => EvaluationPeriodType::Quarterly,
        'period_key' => '2026-Q1',
        'rating' => 3.5,
        'status' => EvaluationStatus::Draft,
    ]);

    Notification::fake();

    $this->actingAs($owner)->post(route('hr.evaluations.approve'), [
        'period_type' => 'quarterly',
        'period_key' => '2026-Q1',
    ])->assertRedirect();

    Notification::assertSentTo($reportUser, EvaluationPublishedNotification::class);
});

test('a submitted leave request reaches the hr manager, not only owners', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $hrUser = User::factory()->create(['tenant_id' => $owner->tenant_id, 'is_active' => true]);
    $hrUser->assignRole(TenantPermissionCatalog::ROLE_HR_MANAGER);

    $meUser = User::factory()->create(['tenant_id' => $owner->tenant_id, 'is_active' => true]);
    $meUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $me = Employee::factory()->create(['tenant_id' => $owner->tenant_id, 'user_id' => $meUser->id]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'annual_days' => 20,
        'requires_approval' => true,
    ]);

    Notification::fake();

    $this->actingAs($meUser)->post(route('tenant.hr.my-leaves.store'), [
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
        'reason' => 'ظرف عائلي',
    ])->assertRedirect();

    // The HR Manager is the one who approves it — before this work they received nothing.
    Notification::assertSentTo($hrUser, NewLeaveRequestNotification::class);
    // Owners keep their existing delivery.
    Notification::assertSentTo($owner, NewLeaveRequestNotification::class);
});

test('an owner who is also hr manager is not notified twice for the same event', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $owner->assignRole(TenantPermissionCatalog::ROLE_HR_MANAGER);

    $meUser = User::factory()->create(['tenant_id' => $owner->tenant_id, 'is_active' => true]);
    $meUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    Employee::factory()->create(['tenant_id' => $owner->tenant_id, 'user_id' => $meUser->id]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'annual_days' => 20,
        'requires_approval' => true,
    ]);

    Notification::fake();

    $this->actingAs($meUser)->post(route('tenant.hr.my-leaves.store'), [
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(3)->toDateString(),
        'end_date' => now()->addDays(4)->toDateString(),
    ])->assertRedirect();

    Notification::assertSentToTimes($owner, NewLeaveRequestNotification::class, 1);
});

test('notifications never cross tenant boundaries', function () {
    $ownerA = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $ownerB = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $hrB = User::factory()->create(['tenant_id' => $ownerB->tenant_id, 'is_active' => true]);
    $hrB->assignRole(TenantPermissionCatalog::ROLE_HR_MANAGER);

    // Act inside tenant A.
    $this->actingAs($ownerA);
    app(TenantContext::class)->setTenant($ownerA->tenant);

    [$managerUserA, , $reportUserA, $reportA] = taskPair($ownerA->tenant_id);

    Notification::fake();

    $this->actingAs($managerUserA)->post(route('hr.tasks.store'), [
        'employee_id' => $reportA->id,
        'title' => 'مهمة داخل المستأجر أ',
    ])->assertRedirect();

    Notification::assertSentTo($reportUserA, TaskAssignedNotification::class);
    Notification::assertNothingSentTo($ownerB);
    Notification::assertNothingSentTo($hrB);
});
