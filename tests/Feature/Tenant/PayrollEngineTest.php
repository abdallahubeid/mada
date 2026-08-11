<?php

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Finance\Exceptions\PayrollRunException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItemType;
use App\Domain\Finance\Support\WorkLedgerSummary;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Enums\WorkLedgerDayType;
use App\Domain\Tenancy\Enums\WorkLedgerSource;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\WorkLedgerEntry;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Services\Finance\PayrollRunBuilder;
use App\Services\Finance\PayslipCalculator;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function calculator(): PayslipCalculator
{
    return new PayslipCalculator;
}

/**
 * August 2026 holds exactly 21 weekdays (Aug 1 is a Saturday, first weekday
 * Aug 3). A 630000 monthly rate therefore divides to exactly 30000/day, which
 * keeps every expectation below in round numbers.
 *
 * The plan must not exceed 21 days: a 22nd entry would land on Sep 1 and be
 * silently excluded by the period filter, quietly changing the day counts the
 * assertions depend on.
 */
const AUGUST_2026_WEEKDAYS = 21;

function seedAugustLedger(int $employeeId, int $present = 19, int $absent = 2, int $excused = 0, int $unresolved = 0): void
{
    $plan = array_merge(
        array_fill(0, $present, WorkLedgerDayType::Present),
        array_fill(0, $absent, WorkLedgerDayType::Absent),
        array_fill(0, $excused, WorkLedgerDayType::Excused),
        array_fill(0, $unresolved, WorkLedgerDayType::Workday),
    );

    if (count($plan) > AUGUST_2026_WEEKDAYS) {
        throw new InvalidArgumentException(
            'Ledger plan of '.count($plan).' days overflows August 2026 ('.AUGUST_2026_WEEKDAYS.' weekdays).'
        );
    }

    $date = Carbon::parse('2026-08-03');

    foreach ($plan as $dayType) {
        while ($date->isWeekend()) {
            $date->addDay();
        }

        WorkLedgerEntry::create([
            'employee_id' => $employeeId,
            'date' => $date->toDateString(),
            'day_type' => $dayType,
            'source' => WorkLedgerSource::WorkCalendar,
            'worked_minutes' => $dayType === WorkLedgerDayType::Present ? 480 : null,
        ]);

        $date->addDay();
    }
}

function makePayableEmployee(int $tenantId, array $contractAttributes = []): Employee
{
    $employee = Employee::factory()->create(['tenant_id' => $tenantId]);

    EmployeeContract::factory()->create(array_merge([
        'tenant_id' => $tenantId,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => 630_000,
        'pay_currency' => 'SAR',
        'start_date' => '2026-01-01',
        'end_date' => null,
    ], $contractAttributes));

    return $employee;
}

// ---------------------------------------------------------------------------
// PayslipCalculator — pure arithmetic, no database
// ---------------------------------------------------------------------------

test('a salaried employee with no absences earns the full base', function () {
    $ledger = new WorkLedgerSummary(
        periodScheduledDays: 22, scheduledDays: 22, presentDays: 22, excusedDays: 0, absentDays: 0,
    );

    $totals = calculator()->calculate(PayBasis::Salaried, 660_000, $ledger);

    expect($totals->baseAmount)->toBe(660_000)
        ->and($totals->absenceDeduction)->toBe(0)
        ->and($totals->grossAmount)->toBe(660_000)
        ->and($totals->netAmount)->toBe(660_000);
});

test('absence deduction is sourced from absent days only and is always negative', function () {
    $ledger = new WorkLedgerSummary(
        periodScheduledDays: 22, scheduledDays: 22, presentDays: 18, excusedDays: 2, absentDays: 2,
    );

    $totals = calculator()->calculate(PayBasis::Salaried, 660_000, $ledger);

    // 660000 * 2 / 22 = 60000. Excused days never deduct (BR-401/BR-404).
    expect($totals->absenceDeduction)->toBe(-60_000)
        ->and($totals->netAmount)->toBe(600_000);
});

test('excused days alone never reduce pay', function () {
    $ledger = new WorkLedgerSummary(
        periodScheduledDays: 22, scheduledDays: 22, presentDays: 17, excusedDays: 5, absentDays: 0,
    );

    $totals = calculator()->calculate(PayBasis::Salaried, 660_000, $ledger);

    expect($totals->absenceDeduction)->toBe(0)
        ->and($totals->netAmount)->toBe(660_000);
});

test('a mid period joiner is prorated by their share of the period', function () {
    // BR-605: 10 of 22 working days present in the ledger.
    $ledger = new WorkLedgerSummary(
        periodScheduledDays: 22, scheduledDays: 10, presentDays: 10, excusedDays: 0, absentDays: 0,
    );

    $totals = calculator()->calculate(PayBasis::Salaried, 660_000, $ledger);

    expect($totals->baseAmount)->toBe(300_000)
        ->and($totals->netAmount)->toBe(300_000);
});

test('hourly pay is earned per minute worked and carries no absence deduction', function () {
    // Deducting an hourly employee for time they were never paid for
    // would penalize the same absence twice.
    $ledger = new WorkLedgerSummary(
        periodScheduledDays: 22, scheduledDays: 22, presentDays: 18, excusedDays: 0, absentDays: 4,
        workedMinutes: 5_400,
    );

    $totals = calculator()->calculate(PayBasis::Hourly, 5_000, $ledger);

    // 5000 per hour * 5400 minutes / 60 = 450000
    expect($totals->baseAmount)->toBe(450_000)
        ->and($totals->absenceDeduction)->toBe(0)
        ->and($totals->netAmount)->toBe(450_000);
});

test('an unpaid basis earns nothing but still receives line items', function () {
    // BR-612: unpaid employees still appear in the run.
    $ledger = new WorkLedgerSummary(
        periodScheduledDays: 22, scheduledDays: 22, presentDays: 20, excusedDays: 0, absentDays: 2,
    );

    $totals = calculator()->calculate(PayBasis::Unpaid, 660_000, $ledger, [25_000]);

    expect($totals->baseAmount)->toBe(0)
        ->and($totals->absenceDeduction)->toBe(0)
        ->and($totals->allowancesTotal)->toBe(25_000)
        ->and($totals->netAmount)->toBe(25_000);
});

test('line items split by sign and net follows one arithmetic rule', function () {
    $ledger = new WorkLedgerSummary(
        periodScheduledDays: 22, scheduledDays: 22, presentDays: 20, excusedDays: 0, absentDays: 2,
    );

    $totals = calculator()->calculate(PayBasis::Salaried, 660_000, $ledger, [50_000, -20_000, 10_000, -5_000]);

    expect($totals->allowancesTotal)->toBe(60_000)
        ->and($totals->deductionsTotal)->toBe(-25_000)
        ->and($totals->grossAmount)->toBe(720_000)
        ->and($totals->netAmount)->toBe(635_000)
        ->and($totals->netAmount)->toBe(
            $totals->grossAmount + $totals->absenceDeduction + $totals->deductionsTotal
        );
});

test('rounding is exact integer half up with no float in the path', function () {
    // 100000 / 3 = 33333.33 -> 33333 (rounds down)
    $third = calculator()->calculate(
        PayBasis::Salaried, 100_000,
        new WorkLedgerSummary(periodScheduledDays: 3, scheduledDays: 3, presentDays: 2, excusedDays: 0, absentDays: 1),
    );
    expect($third->absenceDeduction)->toBe(-33_333)->toBeInt();

    // 5 / 2 = 2.5 -> 3 (half rounds up)
    $half = calculator()->calculate(
        PayBasis::Salaried, 5,
        new WorkLedgerSummary(periodScheduledDays: 2, scheduledDays: 2, presentDays: 1, excusedDays: 0, absentDays: 1),
    );
    expect($half->absenceDeduction)->toBe(-3)->toBeInt();
});

test('a zero scheduled period never divides by zero', function () {
    $ledger = WorkLedgerSummary::empty();

    $totals = calculator()->calculate(PayBasis::Salaried, 660_000, $ledger);

    expect($totals->baseAmount)->toBe(660_000)
        ->and($totals->absenceDeduction)->toBe(0);
});

test('an unresolved ledger summary reports itself as unpayable', function () {
    $resolved = new WorkLedgerSummary(22, 22, 20, 0, 2);
    $unresolved = new WorkLedgerSummary(22, 22, 18, 0, 2, unresolvedDays: 2);

    expect($resolved->isFullyResolved())->toBeTrue()
        ->and($unresolved->isFullyResolved())->toBeFalse();
});

// ---------------------------------------------------------------------------
// PayrollRunBuilder — guards and assembly
// ---------------------------------------------------------------------------

test('a draft run is built with snapshotted payslips and rolled up totals', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id);
    seedAugustLedger($employee->id);

    PayslipLineItemType::create([
        'name' => 'بدل سكن', 'kind' => PayslipLineItemKind::Allowance, 'default_amount' => 50_000,
    ]);
    PayslipLineItemType::create([
        'name' => 'تأمينات', 'kind' => PayslipLineItemKind::Deduction, 'default_amount' => 20_000,
    ]);

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);

    expect($run->status)->toBe(PayrollRunStatus::Draft)
        ->and($run->currency)->toBe('SAR')
        ->and($run->maker_id)->toBe($user->id)
        ->and($run->payslip_count)->toBe(1)
        ->and($run->period_start->toDateString())->toBe('2026-08-01')
        ->and($run->period_end->toDateString())->toBe('2026-08-31');

    $payslip = $run->payslips()->first();

    // Snapshot (BR-608)
    expect($payslip->employee_name)->toBe($employee->full_name)
        ->and($payslip->pay_basis)->toBe(PayBasis::Salaried)
        ->and($payslip->base_rate)->toBe(630_000)
        ->and($payslip->pay_currency)->toBe('SAR')
        ->and($payslip->period_scheduled_days)->toBe(21)
        ->and($payslip->scheduled_days)->toBe(21)
        ->and($payslip->present_days)->toBe(19)
        ->and($payslip->absent_days)->toBe(2);

    // Money: 630000 base - 60000 absence + 50000 allowance - 20000 deduction
    expect($payslip->base_amount)->toBe(630_000)
        ->and($payslip->absence_deduction)->toBe(-60_000)
        ->and($payslip->allowances_total)->toBe(50_000)
        ->and($payslip->deductions_total)->toBe(-20_000)
        ->and($payslip->gross_amount)->toBe(680_000)
        ->and($payslip->net_amount)->toBe(600_000);

    expect($payslip->lineItems)->toHaveCount(2)
        ->and($run->total_net)->toBe(600_000)
        ->and($run->total_gross)->toBe(680_000);
});

test('a deduction type with a positive default still subtracts', function () {
    // Sign is derived from kind, so a misconfigured type cannot add money.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id);
    seedAugustLedger($employee->id, present: 21, absent: 0);

    PayslipLineItemType::create([
        'name' => 'خصم مضبوط بالموجب', 'kind' => PayslipLineItemKind::Deduction, 'default_amount' => 30_000,
    ]);

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);
    $payslip = $run->payslips()->first();

    expect($payslip->deductions_total)->toBe(-30_000)
        ->and($payslip->lineItems()->first()->amount)->toBe(-30_000)
        ->and($payslip->net_amount)->toBe(600_000);
});

test('a run is refused while the ledger still holds unresolved workdays', function () {
    // BR-405 — the guard that stops unreconciled days being treated as present.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id);
    seedAugustLedger($employee->id, present: 17, absent: 2, excused: 0, unresolved: 2);

    expect(fn () => app(PayrollRunBuilder::class)->build('2026-08', $user))
        ->toThrow(PayrollRunException::class, 'unresolved');

    expect(PayrollRun::query()->count())->toBe(0);
});

test('a run is refused while any active contract has no pay rate', function () {
    // BR-301a — the migration backfilled every contract to salaried/0.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id, ['base_rate' => 0]);
    seedAugustLedger($employee->id);

    expect(fn () => app(PayrollRunBuilder::class)->build('2026-08', $user))
        ->toThrow(PayrollRunException::class, 'no pay rate set');

    expect(PayrollRun::query()->count())->toBe(0);
});

test('a second live run for the same period is refused', function () {
    // BR-611
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id);
    seedAugustLedger($employee->id);

    app(PayrollRunBuilder::class)->build('2026-08', $user);

    expect(fn () => app(PayrollRunBuilder::class)->build('2026-08', $user))
        ->toThrow(PayrollRunException::class, 'already exists');

    expect(PayrollRun::query()->count())->toBe(1);
});

test('cancelling a run frees the period for a new one', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id);
    seedAugustLedger($employee->id);

    $first = app(PayrollRunBuilder::class)->build('2026-08', $user);
    $first->update(['status' => PayrollRunStatus::Cancelled]);

    $second = app(PayrollRunBuilder::class)->build('2026-08', $user);

    expect($second->id)->not->toBe($first->id)
        ->and($second->status)->toBe(PayrollRunStatus::Draft);
});

test('contracts in different currencies cannot share a run', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $first = makePayableEmployee($user->tenant_id, ['pay_currency' => 'SAR']);
    $second = makePayableEmployee($user->tenant_id, ['pay_currency' => 'AED']);
    seedAugustLedger($first->id);
    seedAugustLedger($second->id);

    expect(fn () => app(PayrollRunBuilder::class)->build('2026-08', $user))
        ->toThrow(PayrollRunException::class, 'multiple pay currencies');
});

test('a malformed period is rejected before anything is written', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    foreach (['2026-13', '2026-8', 'august', '2026-00'] as $period) {
        expect(fn () => app(PayrollRunBuilder::class)->build($period, $user))
            ->toThrow(PayrollRunException::class, 'not a valid');
    }
});

test('a run with no active contracts is refused rather than created empty', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect(fn () => app(PayrollRunBuilder::class)->build('2026-08', $user))
        ->toThrow(PayrollRunException::class, 'No active employee contracts');
});

test('an employee with two active contracts receives exactly one payslip', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id, ['start_date' => '2026-01-01', 'base_rate' => 400_000]);

    EmployeeContract::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => 630_000,
        'pay_currency' => 'SAR',
        'start_date' => '2026-06-01',
    ]);

    seedAugustLedger($employee->id);

    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);

    // The most recently started contract wins.
    expect($run->payslips)->toHaveCount(1)
        ->and($run->payslips->first()->base_rate)->toBe(630_000);
});

test('payroll runs and payslips are invisible across tenants', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($user->tenant_id);
    seedAugustLedger($employee->id);

    app(PayrollRunBuilder::class)->build('2026-08', $user);

    expect(PayrollRun::query()->count())->toBe(1)
        ->and(Payslip::query()->count())->toBe(1);

    app(TenantContext::class)->setTenant(Tenant::factory()->create());

    expect(PayrollRun::query()->count())->toBe(0)
        ->and(Payslip::query()->count())->toBe(0);

    app(TenantContext::class)->setTenant(null);
});

test('run status reports locking and dashboard eligibility correctly', function () {
    expect(PayrollRunStatus::Draft->isEditable())->toBeTrue()
        ->and(PayrollRunStatus::Draft->isLocked())->toBeFalse()
        ->and(PayrollRunStatus::PendingApproval->isEditable())->toBeFalse()
        ->and(PayrollRunStatus::Approved->isLocked())->toBeTrue()
        ->and(PayrollRunStatus::Paid->isLocked())->toBeTrue()
        ->and(PayrollRunStatus::Cancelled->isLocked())->toBeFalse();

    // BR-607: only finalized runs reach the dashboard.
    expect(PayrollRunStatus::Approved->countsTowardDashboard())->toBeTrue()
        ->and(PayrollRunStatus::Paid->countsTowardDashboard())->toBeTrue()
        ->and(PayrollRunStatus::Draft->countsTowardDashboard())->toBeFalse();
});

test('a run cannot be approved by the user who prepared it', function () {
    // BR-615 — the Owner Gate::before bypass makes this unenforceable by permission.
    $maker = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $employee = makePayableEmployee($maker->tenant_id);
    seedAugustLedger($employee->id);

    $run = app(PayrollRunBuilder::class)->build('2026-08', $maker);
    $run->update(['status' => PayrollRunStatus::PendingApproval]);

    $checker = User::factory()->create(['tenant_id' => $maker->tenant_id]);

    expect($run->fresh()->canBeApprovedBy($maker))->toBeFalse()
        ->and($run->fresh()->canBeApprovedBy($checker))->toBeTrue();
});
