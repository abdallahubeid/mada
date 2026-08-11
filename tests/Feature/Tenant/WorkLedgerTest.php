<?php

use App\Domain\Tenancy\Enums\WorkLedgerDayType;
use App\Domain\Tenancy\Enums\WorkLedgerSource;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\WorkLedgerEntry;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Database\QueryException;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('only an absent day is deductible', function () {
    // BR-404: excused, holiday and weekend must never produce a deduction.
    expect(WorkLedgerDayType::Absent->isDeductible())->toBeTrue();

    foreach ([
        WorkLedgerDayType::Workday,
        WorkLedgerDayType::Weekend,
        WorkLedgerDayType::Holiday,
        WorkLedgerDayType::Excused,
        WorkLedgerDayType::Present,
    ] as $dayType) {
        expect($dayType->isDeductible())->toBeFalse();
    }
});

test('workday is the only unresolved day type', function () {
    // BR-405: a period containing any unresolved day cannot be paid.
    expect(WorkLedgerDayType::Workday->isResolved())->toBeFalse();

    foreach ([
        WorkLedgerDayType::Weekend,
        WorkLedgerDayType::Holiday,
        WorkLedgerDayType::Excused,
        WorkLedgerDayType::Present,
        WorkLedgerDayType::Absent,
    ] as $dayType) {
        expect($dayType->isResolved())->toBeTrue();
    }
});

test('every day type and source carries an arabic label', function () {
    foreach (WorkLedgerDayType::cases() as $dayType) {
        expect($dayType->label())->not->toBe('');
    }

    foreach (WorkLedgerSource::cases() as $source) {
        expect($source->label())->not->toBe('');
    }
});

test('an entry casts its day type and source to enums and delegates deductibility', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = Employee::factory()->create(['tenant_id' => $user->tenant_id]);

    $entry = WorkLedgerEntry::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-03',
        'day_type' => WorkLedgerDayType::Absent,
        'source' => WorkLedgerSource::WorkCalendar,
    ]);

    $fresh = $entry->fresh();

    expect($fresh->day_type)->toBe(WorkLedgerDayType::Absent)
        ->and($fresh->source)->toBe(WorkLedgerSource::WorkCalendar)
        ->and($fresh->isDeductible())->toBeTrue()
        ->and($fresh->tenant_id)->toBe($user->tenant_id);
});

test('worked minutes are stored as an integer, never as float hours', function () {
    // ADR-20: this value multiplies into money on hourly contracts.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = Employee::factory()->create(['tenant_id' => $user->tenant_id]);

    $entry = WorkLedgerEntry::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-04',
        'day_type' => WorkLedgerDayType::Present,
        'source' => WorkLedgerSource::Attendance,
        'worked_minutes' => 487,
    ]);

    expect($entry->fresh()->worked_minutes)->toBe(487)->toBeInt();
});

test('provenance links an entry back to the record that classified it', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $tenantId = $user->tenant_id;

    $employee = Employee::factory()->create(['tenant_id' => $tenantId]);
    $leaveType = LeaveType::factory()->create(['tenant_id' => $tenantId]);

    $attendance = Attendance::factory()->create([
        'tenant_id' => $tenantId,
        'employee_id' => $employee->id,
        'date' => '2026-08-05',
    ]);

    $leave = LeaveRequest::factory()->create([
        'tenant_id' => $tenantId,
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    $present = WorkLedgerEntry::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-05',
        'day_type' => WorkLedgerDayType::Present,
        'source' => WorkLedgerSource::Attendance,
        'attendance_id' => $attendance->id,
    ]);

    $excused = WorkLedgerEntry::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-06',
        'day_type' => WorkLedgerDayType::Excused,
        'source' => WorkLedgerSource::LeaveRequest,
        'leave_request_id' => $leave->id,
    ]);

    expect($present->attendance->id)->toBe($attendance->id)
        ->and($excused->leaveRequest->id)->toBe($leave->id)
        ->and($present->employee->id)->toBe($employee->id);
});

test('one employee cannot hold two ledger rows for the same date', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = Employee::factory()->create(['tenant_id' => $user->tenant_id]);

    $payload = [
        'employee_id' => $employee->id,
        'date' => '2026-08-07',
        'day_type' => WorkLedgerDayType::Present,
        'source' => WorkLedgerSource::Attendance,
    ];

    WorkLedgerEntry::create($payload);

    expect(fn () => WorkLedgerEntry::create($payload))->toThrow(QueryException::class);
});

test('a period rebuild can hard delete and reinsert the same employee dates', function () {
    // BR-406 + ADR-21. This is the regression guard for the soft-delete
    // exception: adding SoftDeletes to WorkLedgerEntry would leave the deleted
    // rows in place and collide with the (tenant, employee, date) unique key,
    // failing this test on the second insert.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = Employee::factory()->create(['tenant_id' => $user->tenant_id]);

    $dates = ['2026-08-10', '2026-08-11', '2026-08-12'];

    $build = function (WorkLedgerDayType $dayType) use ($employee, $dates): void {
        foreach ($dates as $date) {
            WorkLedgerEntry::create([
                'employee_id' => $employee->id,
                'date' => $date,
                'day_type' => $dayType,
                'source' => WorkLedgerSource::WorkCalendar,
            ]);
        }
    };

    $build(WorkLedgerDayType::Workday);
    expect(WorkLedgerEntry::query()->count())->toBe(3);

    // Rebuild: hard delete then reinsert, exactly as the reconciler will.
    WorkLedgerEntry::query()->where('employee_id', $employee->id)->delete();
    expect(WorkLedgerEntry::query()->count())->toBe(0);

    $build(WorkLedgerDayType::Absent);

    expect(WorkLedgerEntry::query()->count())->toBe(3)
        ->and(WorkLedgerEntry::query()->where('day_type', WorkLedgerDayType::Absent)->count())->toBe(3);
});

test('the same date is independently reconcilable for two different employees', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $first = Employee::factory()->create(['tenant_id' => $user->tenant_id]);
    $second = Employee::factory()->create(['tenant_id' => $user->tenant_id]);

    foreach ([$first, $second] as $employee) {
        WorkLedgerEntry::create([
            'employee_id' => $employee->id,
            'date' => '2026-08-13',
            'day_type' => WorkLedgerDayType::Present,
            'source' => WorkLedgerSource::Attendance,
        ]);
    }

    expect(WorkLedgerEntry::query()->count())->toBe(2);
});

test('ledger entries are invisible across tenants under the global scope', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = Employee::factory()->create(['tenant_id' => $user->tenant_id]);

    $entry = WorkLedgerEntry::create([
        'employee_id' => $employee->id,
        'date' => '2026-08-14',
        'day_type' => WorkLedgerDayType::Absent,
        'source' => WorkLedgerSource::WorkCalendar,
    ]);

    expect(WorkLedgerEntry::query()->count())->toBe(1);

    $tenantB = Tenant::factory()->create();
    app(TenantContext::class)->setTenant($tenantB);

    expect(WorkLedgerEntry::query()->count())->toBe(0)
        ->and(WorkLedgerEntry::query()->find($entry->id))->toBeNull();

    app(TenantContext::class)->setTenant(null);
});
