<?php

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('owner can create leave type and leave request then approve with balance deduction', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-01 10:00:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Sara',
        'last_name' => 'Ali',
        'status' => EmployeeStatus::Active,
    ]);

    $this->post(route('hr.leaves.types.store'), [
        'name' => 'سنوية',
        'annual_days' => 14,
        'requires_approval' => '1',
    ])->assertRedirect(route('hr.leaves.index'));

    $leaveType = LeaveType::query()->where('name', 'سنوية')->first();

    expect($leaveType)->not->toBeNull()
        ->and($leaveType->annual_days)->toBe(14)
        ->and($leaveType->remainingDaysFor($employee->id))->toBe(14);

    $this->post(route('hr.leaves.requests.store'), [
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => '2026-08-01',
        'end_date' => '2026-08-03',
        'reason' => 'Family trip',
    ])->assertRedirect();

    $leaveRequest = LeaveRequest::query()->where('employee_id', $employee->id)->first();

    expect($leaveRequest)->not->toBeNull()
        ->and($leaveRequest->days_count)->toBe(3)
        ->and($leaveRequest->status)->toBe(LeaveRequestStatus::Pending);

    $this->get(route('hr.leaves.index'))
        ->assertOk()
        ->assertSee('إدارة الإجازات')
        ->assertSee('Sara Ali')
        ->assertSee('سنوية');

    $this->post(route('hr.leaves.approve', $leaveRequest))->assertRedirect();

    $leaveRequest->refresh();
    $employee->refresh();

    expect($leaveRequest->status)->toBe(LeaveRequestStatus::Approved)
        ->and($leaveRequest->approved_by)->toBe($user->id)
        ->and($leaveType->remainingDaysFor($employee->id))->toBe(11)
        ->and($employee->status)->toBe(EmployeeStatus::OnLeave);

    $this->get(route('hr.employees.show', $employee).'?tab=leaves')
        ->assertOk()
        ->assertSee('الإجازات')
        ->assertSee('سنوية')
        ->assertSee('11');

    Carbon::setTestNow();
});

test('owner can reject a pending leave request without deducting balance', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'مرضية',
        'annual_days' => 10,
        'requires_approval' => true,
    ]);

    $leaveRequest = LeaveRequest::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addWeek()->toDateString(),
        'end_date' => now()->addWeek()->addDays(1)->toDateString(),
        'days_count' => 2,
        'status' => LeaveRequestStatus::Pending,
    ]);

    $this->post(route('hr.leaves.reject', $leaveRequest), [
        'rejection_reason' => 'Overlapping project deadline',
    ])->assertRedirect();

    $leaveRequest->refresh();

    expect($leaveRequest->status)->toBe(LeaveRequestStatus::Rejected)
        ->and($leaveRequest->rejection_reason)->toBe('Overlapping project deadline')
        ->and($leaveType->remainingDaysFor($employee->id))->toBe(10)
        ->and($employee->fresh()->status)->toBe(EmployeeStatus::Active);
});

test('hr manager can access leave and evaluations hubs', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $this->get(route('hr.leaves.index'))->assertOk();
    $this->get(route('hr.evaluations.index'))->assertOk();
});
