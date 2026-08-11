<?php

use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('owner can view employee details with contract and attendance tabs', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Maha',
        'last_name' => 'Nasser',
        'job_title' => 'Operations Lead',
        'national_id' => '1099988877',
        'phone' => '+966500998877',
    ]);

    EmployeeContract::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'contract_type' => ContractType::FullTime,
        'status' => ContractStatus::Active,
        'start_date' => now()->subMonths(3)->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'probation_end_date' => now()->subMonth()->toDateString(),
    ]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'date' => now()->subDay()->toDateString(),
        'check_in' => now()->subDay()->setTime(8, 50),
        'check_out' => now()->subDay()->setTime(17, 10),
        'status' => AttendanceStatus::Present,
    ]);

    $this->get(route('hr.employees.show', $employee))
        ->assertOk()
        ->assertSee('Maha Nasser')
        ->assertSee('Operations Lead')
        ->assertSee('نظرة عامة')
        ->assertSee('العقد النشط')
        ->assertSee('سجل الحضور')
        ->assertSee('1099988877')
        ->assertSee('دوام كامل')
        ->assertSee('تسجيل حضور');
});

test('owner can check in and check out an employee from the attendance hub', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-01 08:45:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Faisal',
        'last_name' => 'Ahmed',
    ]);

    $this->get(route('hr.attendance.index'))
        ->assertOk()
        ->assertSee('سجل الحضور والغياب')
        ->assertSee('Faisal Ahmed');

    $this->post(route('hr.attendance.check-in'), [
        'employee_id' => $employee->id,
    ])->assertRedirect();

    $attendance = Attendance::query()
        ->where('employee_id', $employee->id)
        ->whereDate('date', '2026-08-01')
        ->first();

    expect($attendance)->not->toBeNull()
        ->and($attendance->check_in)->not->toBeNull()
        ->and($attendance->status)->toBe(AttendanceStatus::Present);

    Carbon::setTestNow(Carbon::parse('2026-08-01 17:20:00'));

    $this->post(route('hr.attendance.check-out'), [
        'employee_id' => $employee->id,
    ])->assertRedirect();

    $attendance->refresh();

    expect($attendance->check_out)->not->toBeNull()
        ->and($attendance->workedHoursLabel())->not->toBe('—');

    $this->get(route('hr.attendance.index', ['date' => '2026-08-01']))
        ->assertOk()
        ->assertSee('Faisal Ahmed')
        ->assertSee('حاضر');

    Carbon::setTestNow();
});

test('late check-in is auto-marked when after default threshold', function () {
    Carbon::setTestNow(Carbon::parse('2026-08-01 09:30:00'));

    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $this->post(route('hr.attendance.check-in'), [
        'employee_id' => $employee->id,
    ])->assertRedirect();

    $attendance = Attendance::query()->where('employee_id', $employee->id)->first();

    expect($attendance->status)->toBe(AttendanceStatus::Late);

    Carbon::setTestNow();
});

test('attendance index filters logs by date and employee', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employeeA = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Amina',
        'last_name' => 'Said',
    ]);
    $employeeB = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Bassam',
        'last_name' => 'Yusuf',
    ]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeA->id,
        'date' => '2026-07-20',
        'status' => AttendanceStatus::Present,
    ]);
    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeB->id,
        'date' => '2026-07-20',
        'status' => AttendanceStatus::Late,
    ]);
    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employeeA->id,
        'date' => '2026-07-21',
        'status' => AttendanceStatus::Present,
    ]);

    $response = $this->get(route('hr.attendance.index', [
        'date' => '2026-07-20',
        'employee_id' => $employeeA->id,
    ]))->assertOk();

    $logs = $response->viewData('logs');

    expect($logs)->toHaveCount(1)
        ->and($logs->first()->employee_id)->toBe($employeeA->id)
        ->and($logs->first()->employee?->full_name)->toBe('Amina Said');
});
