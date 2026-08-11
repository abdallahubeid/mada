<?php

use App\Domain\Tenancy\Enums\TaskStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Task;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

function makeManagerWithReport(int $tenantId): array
{
    $managerUser = User::factory()->create(['tenant_id' => $tenantId, 'is_active' => true]);
    $managerUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);

    $manager = Employee::factory()->create([
        'tenant_id' => $tenantId,
        'user_id' => $managerUser->id,
        'first_name' => 'مدير',
        'last_name' => 'مباشر',
    ]);

    $reportUser = User::factory()->create(['tenant_id' => $tenantId, 'is_active' => true]);
    $reportUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);

    $report = Employee::factory()->create([
        'tenant_id' => $tenantId,
        'user_id' => $reportUser->id,
        'manager_id' => $manager->id,
        'first_name' => 'مرؤوس',
        'last_name' => 'أول',
    ]);

    return [$managerUser, $manager, $reportUser, $report];
}

test('line manager can assign a task to a direct report but not to an outside employee', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    [$managerUser, $manager, , $report] = makeManagerWithReport($owner->tenant_id);

    $outsider = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'first_name' => 'خارج',
        'last_name' => 'النطاق',
    ]);

    $this->actingAs($managerUser)
        ->get(route('hr.tasks.index'))
        ->assertOk()
        ->assertSee('مرؤوس أول', false)
        ->assertDontSee('خارج النطاق', false);

    $this->actingAs($managerUser)
        ->post(route('hr.tasks.store'), [
            'employee_id' => $report->id,
            'title' => 'إعداد التقرير الشهري',
            'description' => 'تجهيز تقرير المبيعات',
            'due_date' => now()->addDays(5)->toDateString(),
            'priority' => 'high',
        ])
        ->assertRedirect(route('hr.tasks.index'));

    $task = Task::query()->where('employee_id', $report->id)->first();

    expect($task)->not->toBeNull()
        ->and($task->manager_id)->toBe($manager->id)
        ->and($task->title)->toBe('إعداد التقرير الشهري')
        ->and($task->status)->toBe(TaskStatus::Todo);

    $this->actingAs($managerUser)
        ->post(route('hr.tasks.store'), [
            'employee_id' => $outsider->id,
            'title' => 'مهمة غير مصرح بها',
        ])
        ->assertSessionHasErrors('employee_id');

    expect(Task::query()->where('employee_id', $outsider->id)->exists())->toBeFalse();
});

test('employee without direct reports cannot access the task assignment hub', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('hr.tasks.index'))->assertForbidden();
});

test('employee can move their own task across scrum columns', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    [, $manager, $reportUser, $report] = makeManagerWithReport($owner->tenant_id);

    $task = Task::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'manager_id' => $manager->id,
        'employee_id' => $report->id,
        'status' => TaskStatus::Todo,
    ]);

    $this->actingAs($reportUser)
        ->get(route('tenant.hr.my-tasks'))
        ->assertOk()
        ->assertSee($task->title, false)
        ->assertSee('لتنفيذه', false);

    foreach ([TaskStatus::InProgress, TaskStatus::Review, TaskStatus::Completed] as $next) {
        $this->actingAs($reportUser)
            ->post(route('tenant.hr.my-tasks.status', $task), ['status' => $next->value])
            ->assertRedirect(route('tenant.hr.my-tasks'));

        expect($task->fresh()->status)->toBe($next);
    }
});

test('an employee cannot update the status of a co-worker task', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    [, $manager, , $report] = makeManagerWithReport($owner->tenant_id);

    $otherUser = User::factory()->create(['tenant_id' => $owner->tenant_id, 'is_active' => true]);
    $otherUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'user_id' => $otherUser->id,
        'manager_id' => $manager->id,
        'first_name' => 'زميل',
        'last_name' => 'آخر',
    ]);

    $task = Task::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'manager_id' => $manager->id,
        'employee_id' => $report->id,
        'status' => TaskStatus::Todo,
    ]);

    $this->actingAs($otherUser)
        ->post(route('tenant.hr.my-tasks.status', $task), ['status' => 'in_progress'])
        ->assertForbidden();

    expect($task->fresh()->status)->toBe(TaskStatus::Todo);
});

test('a task from another tenant is not reachable through the status update route', function () {
    $ownerA = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [, $managerA, $reportUserA, $reportA] = makeManagerWithReport($ownerA->tenant_id);

    $ownerB = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [, $managerB, , $reportB] = makeManagerWithReport($ownerB->tenant_id);

    $foreignTask = Task::factory()->create([
        'tenant_id' => $ownerB->tenant_id,
        'manager_id' => $managerB->id,
        'employee_id' => $reportB->id,
        'status' => TaskStatus::Todo,
    ]);

    $this->actingAs($reportUserA)
        ->post(route('tenant.hr.my-tasks.status', $foreignTask), ['status' => 'in_progress'])
        ->assertNotFound();

    expect($foreignTask->fresh()->status)->toBe(TaskStatus::Todo);
});
