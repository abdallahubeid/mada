<?php

use App\Domain\Finance\Actions\ApprovePayrollRun;
use App\Domain\Finance\Actions\MarkPayrollRunPaid;
use App\Domain\Finance\Actions\RejectPayrollRun;
use App\Domain\Finance\Actions\SubmitPayrollRunForApproval;
use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\LockedFinancialRecordException;
use App\Domain\Finance\Exceptions\PayrollRunException;
use App\Domain\Finance\Exceptions\PayrollRunTransitionException;
use App\Domain\Finance\Exceptions\WorkLedgerException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Enums\WorkLedgerDayType;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\OfficialHoliday;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\Models\WorkLedgerEntry;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Services\Finance\PayrollRunBuilder;
use App\Services\Finance\WorkLedgerReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * August 2026 with a Fri/Sat weekend (Carbon dayOfWeek 5 and 6):
 *   Mon 3, Tue 4, Wed 5, Thu 6 = working; Fri 7, Sat 8 = weekend; Sun 9 = working.
 */
function reconcilerWeek(): array
{
    return [Carbon::parse('2026-08-03'), Carbon::parse('2026-08-09')];
}

function makeFridaySaturdayCalendar(int $tenantId): WorkCalendar
{
    return WorkCalendar::create([
        'tenant_id' => $tenantId,
        'name' => 'Default',
        'working_days' => [0, 1, 2, 3, 4],
        'weekend_days' => [5, 6],
    ]);
}

function makeActiveEmployee(int $tenantId, int $baseRate = 630_000): Employee
{
    $employee = Employee::factory()->create(['tenant_id' => $tenantId]);

    EmployeeContract::factory()->create([
        'tenant_id' => $tenantId,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => $baseRate,
        'pay_currency' => 'SAR',
        'start_date' => '2026-01-01',
        'end_date' => null,
    ]);

    return $employee;
}

/** @return array{0: User, 1: Employee} */
function reconciledTenant(): array
{
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    makeFridaySaturdayCalendar($user->tenant_id);
    $employee = makeActiveEmployee($user->tenant_id);

    app(WorkLedgerReconciler::class)->reconcilePeriod(
        Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31')
    );

    return [$user, $employee];
}

// ---------------------------------------------------------------------------
// WorkLedgerReconciler
// ---------------------------------------------------------------------------

test('reconciliation classifies each day by precedence', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    makeFridaySaturdayCalendar($user->tenant_id);
    $employee = makeActiveEmployee($user->tenant_id);
    $leaveType = LeaveType::factory()->create(['tenant_id' => $user->tenant_id]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id, 'employee_id' => $employee->id,
        'date' => '2026-08-03', 'status' => AttendanceStatus::Present,
        'check_in' => '2026-08-03 08:00:00', 'check_out' => '2026-08-03 16:00:00',
    ]);

    LeaveRequest::factory()->create([
        'tenant_id' => $user->tenant_id, 'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id, 'status' => LeaveRequestStatus::Approved,
        'start_date' => '2026-08-04', 'end_date' => '2026-08-04', 'days_count' => 1,
    ]);

    OfficialHoliday::factory()->create([
        'tenant_id' => $user->tenant_id, 'name' => 'يوم وطني',
        'start_date' => '2026-08-06', 'end_date' => '2026-08-06', 'is_recurring' => false,
    ]);

    [$start, $end] = reconcilerWeek();
    $written = app(WorkLedgerReconciler::class)->reconcile([$employee->id], $start, $end);

    $byDate = WorkLedgerEntry::query()->get()->keyBy(fn ($e) => $e->date->toDateString());

    expect($written)->toBe(7)
        ->and($byDate['2026-08-03']->day_type)->toBe(WorkLedgerDayType::Present)
        ->and($byDate['2026-08-03']->worked_minutes)->toBe(480)
        ->and($byDate['2026-08-04']->day_type)->toBe(WorkLedgerDayType::Excused)
        ->and($byDate['2026-08-05']->day_type)->toBe(WorkLedgerDayType::Absent)
        ->and($byDate['2026-08-06']->day_type)->toBe(WorkLedgerDayType::Holiday)
        ->and($byDate['2026-08-07']->day_type)->toBe(WorkLedgerDayType::Weekend)
        ->and($byDate['2026-08-08']->day_type)->toBe(WorkLedgerDayType::Weekend)
        ->and($byDate['2026-08-09']->day_type)->toBe(WorkLedgerDayType::Absent);
});

test('approved leave outranks attendance so a leave day is never deducted', function () {
    // BR-401/ADR-06: an employee who checked in on an approved leave day must
    // not be double-counted, and must never be marked absent for it.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    makeFridaySaturdayCalendar($user->tenant_id);
    $employee = makeActiveEmployee($user->tenant_id);
    $leaveType = LeaveType::factory()->create(['tenant_id' => $user->tenant_id]);

    Attendance::factory()->create([
        'tenant_id' => $user->tenant_id, 'employee_id' => $employee->id,
        'date' => '2026-08-03', 'status' => AttendanceStatus::Present,
    ]);

    LeaveRequest::factory()->create([
        'tenant_id' => $user->tenant_id, 'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id, 'status' => LeaveRequestStatus::Approved,
        'start_date' => '2026-08-03', 'end_date' => '2026-08-03', 'days_count' => 1,
    ]);

    app(WorkLedgerReconciler::class)->reconcile([$employee->id], Carbon::parse('2026-08-03'), Carbon::parse('2026-08-03'));

    $entry = WorkLedgerEntry::query()->first();

    expect($entry->day_type)->toBe(WorkLedgerDayType::Excused)
        ->and($entry->isDeductible())->toBeFalse();
});

test('pending leave does not excuse an absence', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    makeFridaySaturdayCalendar($user->tenant_id);
    $employee = makeActiveEmployee($user->tenant_id);
    $leaveType = LeaveType::factory()->create(['tenant_id' => $user->tenant_id]);

    LeaveRequest::factory()->create([
        'tenant_id' => $user->tenant_id, 'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id, 'status' => LeaveRequestStatus::Pending,
        'start_date' => '2026-08-03', 'end_date' => '2026-08-03', 'days_count' => 1,
    ]);

    app(WorkLedgerReconciler::class)->reconcile([$employee->id], Carbon::parse('2026-08-03'), Carbon::parse('2026-08-03'));

    expect(WorkLedgerEntry::query()->first()->day_type)->toBe(WorkLedgerDayType::Absent);
});

test('rebuilding a period is idempotent', function () {
    // BR-406. Also the regression guard for the hard-delete decision: with
    // SoftDeletes the second pass would collide on the unique key.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    makeFridaySaturdayCalendar($user->tenant_id);
    $employee = makeActiveEmployee($user->tenant_id);

    [$start, $end] = reconcilerWeek();
    $reconciler = app(WorkLedgerReconciler::class);

    $reconciler->reconcile([$employee->id], $start, $end);
    $first = WorkLedgerEntry::query()->orderBy('date')->pluck('day_type')->all();

    $reconciler->reconcile([$employee->id], $start, $end);
    $second = WorkLedgerEntry::query()->orderBy('date')->pluck('day_type')->all();

    expect(WorkLedgerEntry::query()->count())->toBe(7)
        ->and($second)->toBe($first);
});

test('a rebuild reflects newly approved leave', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    makeFridaySaturdayCalendar($user->tenant_id);
    $employee = makeActiveEmployee($user->tenant_id);
    $leaveType = LeaveType::factory()->create(['tenant_id' => $user->tenant_id]);

    $reconciler = app(WorkLedgerReconciler::class);
    $reconciler->reconcile([$employee->id], Carbon::parse('2026-08-05'), Carbon::parse('2026-08-05'));

    expect(WorkLedgerEntry::query()->first()->day_type)->toBe(WorkLedgerDayType::Absent);

    LeaveRequest::factory()->create([
        'tenant_id' => $user->tenant_id, 'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id, 'status' => LeaveRequestStatus::Approved,
        'start_date' => '2026-08-05', 'end_date' => '2026-08-05', 'days_count' => 1,
    ]);

    $reconciler->reconcile([$employee->id], Carbon::parse('2026-08-05'), Carbon::parse('2026-08-05'));

    expect(WorkLedgerEntry::query()->first()->day_type)->toBe(WorkLedgerDayType::Excused);
});

test('an inverted date range is rejected', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect(fn () => app(WorkLedgerReconciler::class)
        ->reconcile([1], Carbon::parse('2026-08-10'), Carbon::parse('2026-08-01')))
        ->toThrow(WorkLedgerException::class, 'precedes its start');
});

test('a period frozen by a locked run refuses to rebuild', function () {
    // BR-407 — what makes rebuilding safe everywhere else.
    [$user, $employee] = reconciledTenant();

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);
    app(SubmitPayrollRunForApproval::class)->handle($run);
    app(ApprovePayrollRun::class)->handle($run->refresh(), User::factory()->create(['tenant_id' => $user->tenant_id]));

    expect(fn () => app(WorkLedgerReconciler::class)
        ->reconcilePeriod(Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31')))
        ->toThrow(WorkLedgerException::class, 'frozen this period');
});

// ---------------------------------------------------------------------------
// Empty-ledger guard
// ---------------------------------------------------------------------------

test('a run is refused when the ledger for the period is empty', function () {
    // The dangerous case: zero rows passes the unresolved-days check, then the
    // calculator falls back to the full base rate and pays everyone in full.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    makeFridaySaturdayCalendar($user->tenant_id);
    makeActiveEmployee($user->tenant_id);

    expect(fn () => app(PayrollRunBuilder::class)->build('2026-08', $user))
        ->toThrow(PayrollRunException::class, 'is empty');

    expect(PayrollRun::query()->count())->toBe(0);
});

test('a reconciled period builds a run with real ledger derived figures', function () {
    [$user, $employee] = reconciledTenant();

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);
    $payslip = $run->payslips()->first();

    // The reconciler produced the day counts; nothing was hand-seeded.
    expect($payslip->period_scheduled_days)->toBeGreaterThan(0)
        ->and($payslip->scheduled_days)->toBe($payslip->period_scheduled_days)
        ->and($payslip->absent_days)->toBe($payslip->scheduled_days)
        ->and($payslip->net_amount)->toBe(0);
});

// ---------------------------------------------------------------------------
// State transitions
// ---------------------------------------------------------------------------

test('a run moves draft to pending approval to approved to paid', function () {
    [$user] = reconciledTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);
    expect($run->status)->toBe(PayrollRunStatus::Draft);

    $run = app(SubmitPayrollRunForApproval::class)->handle($run);
    expect($run->status)->toBe(PayrollRunStatus::PendingApproval);

    $run = app(ApprovePayrollRun::class)->handle($run, $checker);
    expect($run->status)->toBe(PayrollRunStatus::Approved)
        ->and($run->approver_id)->toBe($checker->id)
        ->and($run->approved_at)->not->toBeNull()
        ->and($run->isLocked())->toBeTrue();

    $run = app(MarkPayrollRunPaid::class)->handle($run);
    expect($run->status)->toBe(PayrollRunStatus::Paid)
        ->and($run->paid_at)->not->toBeNull();
});

test('the maker cannot approve their own run', function () {
    // BR-615 — remove the canBeApprovedBy check and this test fails.
    [$user] = reconciledTenant();

    $run = app(SubmitPayrollRunForApproval::class)
        ->handle(app(PayrollRunBuilder::class)->build('2026-08', $user));

    expect(fn () => app(ApprovePayrollRun::class)->handle($run, $user))
        ->toThrow(PayrollRunTransitionException::class, 'may not also approve');

    expect($run->refresh()->status)->toBe(PayrollRunStatus::PendingApproval);
});

test('rejection returns the run to draft with a recorded reason', function () {
    [$user] = reconciledTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(SubmitPayrollRunForApproval::class)
        ->handle(app(PayrollRunBuilder::class)->build('2026-08', $user));

    $run = app(RejectPayrollRun::class)->handle($run, $checker, 'بدل السكن غير صحيح');

    expect($run->status)->toBe(PayrollRunStatus::Draft)
        ->and($run->rejection_reason)->toBe('بدل السكن غير صحيح')
        ->and($run->approved_at)->toBeNull();
});

test('rejection requires a reason', function () {
    [$user] = reconciledTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(SubmitPayrollRunForApproval::class)
        ->handle(app(PayrollRunBuilder::class)->build('2026-08', $user));

    expect(fn () => app(RejectPayrollRun::class)->handle($run, $checker, '   '))
        ->toThrow(PayrollRunTransitionException::class, 'requires a reason');
});

test('transitions out of order are refused', function () {
    [$user] = reconciledTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);

    expect(fn () => app(ApprovePayrollRun::class)->handle($run, $checker))
        ->toThrow(PayrollRunTransitionException::class, 'expected status');

    expect(fn () => app(MarkPayrollRunPaid::class)->handle($run))
        ->toThrow(PayrollRunTransitionException::class, 'expected status');

    app(SubmitPayrollRunForApproval::class)->handle($run);

    expect(fn () => app(SubmitPayrollRunForApproval::class)->handle($run->refresh()))
        ->toThrow(PayrollRunTransitionException::class, 'expected status');
});

// ---------------------------------------------------------------------------
// Immutability observers (BR-610, NFR-11)
// ---------------------------------------------------------------------------

/** @return array{0: PayrollRun, 1: User} */
function lockedRun(): array
{
    [$user] = reconciledTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(SubmitPayrollRunForApproval::class)
        ->handle(app(PayrollRunBuilder::class)->build('2026-08', $user));

    return [app(ApprovePayrollRun::class)->handle($run, $checker), $user];
}

test('an approved run rejects edits to its figures', function () {
    [$run] = lockedRun();

    expect(fn () => $run->update(['total_net' => 1]))
        ->toThrow(LockedFinancialRecordException::class, 'is locked');

    expect(fn () => $run->update(['notes' => 'tweak']))
        ->toThrow(LockedFinancialRecordException::class, 'is locked');
});

test('an approved run cannot be deleted or force deleted', function () {
    // BR-617, extended: soft delete is blocked too, because it would free
    // active_period and let a second run claim the month.
    [$run] = lockedRun();

    expect(fn () => $run->delete())
        ->toThrow(LockedFinancialRecordException::class, 'deletion is not permitted');

    expect(fn () => $run->forceDelete())
        ->toThrow(LockedFinancialRecordException::class, 'permanent deletion');

    expect(PayrollRun::query()->count())->toBe(1);
});

test('marking an approved run paid is the one permitted mutation', function () {
    [$run] = lockedRun();

    $paid = app(MarkPayrollRunPaid::class)->handle($run);

    expect($paid->status)->toBe(PayrollRunStatus::Paid);

    // ...and a paid run is locked again for everything.
    expect(fn () => $paid->update(['total_net' => 99]))
        ->toThrow(LockedFinancialRecordException::class);
});

test('payslips and line items under a locked run are immutable', function () {
    [$run] = lockedRun();

    $payslip = $run->payslips()->first();

    expect(fn () => $payslip->update(['net_amount' => 1]))
        ->toThrow(LockedFinancialRecordException::class, 'locked payroll run');

    expect(fn () => $payslip->delete())
        ->toThrow(LockedFinancialRecordException::class);
});

test('a draft run remains fully editable', function () {
    [$user] = reconciledTenant();

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);
    $run->update(['notes' => 'قيد المراجعة']);

    expect($run->refresh()->notes)->toBe('قيد المراجعة');

    $run->delete();

    expect(PayrollRun::query()->count())->toBe(0)
        ->and(PayrollRun::withTrashed()->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Separation of duties in the permission catalog
// ---------------------------------------------------------------------------

test('the finance manager template prepares payroll but cannot approve it', function () {
    // ADR-09: granting approve here would collapse maker-checker into one role.
    $granted = TenantPermissionCatalog::rolePermissionMap()[TenantPermissionCatalog::ROLE_FINANCE_MANAGER];

    expect($granted)->toContain('finance.payroll.prepare')
        ->and($granted)->toContain('finance.payroll.pay')
        ->and($granted)->toContain('finance.payroll.view_any')
        ->and($granted)->not->toContain('finance.payroll.approve');
});

test('a finance manager user resolves those permissions at runtime', function () {
    $financeManager = actingAsTenantUser(TenantPermissionCatalog::ROLE_FINANCE_MANAGER, ['status' => 'active']);

    expect($financeManager->can('finance.payroll.prepare'))->toBeTrue()
        ->and($financeManager->can('finance.payroll.approve'))->toBeFalse();
});

test('an employee cannot touch payroll at all', function () {
    $employee = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    expect($employee->can('finance.payroll.view_any'))->toBeFalse()
        ->and($employee->can('finance.payroll.prepare'))->toBeFalse()
        ->and($employee->can('finance.payroll.approve'))->toBeFalse()
        ->and($employee->can('hr.my_payslips.view'))->toBeTrue();
});
