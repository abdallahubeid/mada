<?php

use App\Domain\Finance\Actions\ApproveSettlementAction;
use App\Domain\Finance\Actions\DisburseSettlementAction;
use App\Domain\Finance\Actions\GenerateSettlementAction;
use App\Domain\Finance\Actions\SubmitSettlementAction;
use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Exceptions\SettlementException;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Services\Finance\OffboardingCalculator;
use App\Services\Finance\WorkLedgerReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

function calculator2(): OffboardingCalculator
{
    return new OffboardingCalculator;
}

/**
 * @return array{0: User, 1: Employee}
 */
function offboardingTenant(int $baseRate = 1_200_000, string $joiningDate = '2020-08-01'): array
{
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    WorkCalendar::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Default',
        'working_days' => [0, 1, 2, 3, 4],
        'weekend_days' => [5, 6],
    ]);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'joining_date' => $joiningDate,
        'status' => EmployeeStatus::Active,
    ]);

    EmployeeContract::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => $baseRate,
        'pay_currency' => 'SAR',
        'start_date' => $joiningDate,
        'end_date' => null,
    ]);

    app(WorkLedgerReconciler::class)->reconcilePeriod(
        Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31')
    );

    return [$user, $employee];
}

// ---------------------------------------------------------------------------
// Calculator precision — pure, no database
// ---------------------------------------------------------------------------

test('eosb accrues at half a month per year for the first five years', function () {
    // 12,000.00/month, exactly 5 years (60 months), termination = full rate.
    // 12000 * 60/12 * 0.5 = 30,000.00
    $result = calculator2()->calculate(
        PayBasis::Salaried, 1_200_000, 60, OffboardingReason::Termination, 0
    );

    expect($result->eosbAmount)->toBe(3_000_000)->toBeInt();
});

test('eosb accrues at a full month per year beyond five years', function () {
    // 10 years: 5 years at half (30,000) + 5 years at full (60,000) = 90,000.00
    $result = calculator2()->calculate(
        PayBasis::Salaried, 1_200_000, 120, OffboardingReason::Termination, 0
    );

    expect($result->eosbAmount)->toBe(9_000_000);
});

test('partial years accrue proportionally rather than being floored', function () {
    // 30 months = 2.5 years at half rate: 12000 * 30/12 * 0.5 = 15,000.00
    $result = calculator2()->calculate(
        PayBasis::Salaried, 1_200_000, 30, OffboardingReason::Termination, 0
    );

    expect($result->eosbAmount)->toBe(1_500_000);
});

test('resignation tapers the entitlement by length of service', function () {
    $rate = 1_200_000;

    // Under 2 years: nothing.
    expect(calculator2()->calculate(PayBasis::Salaried, $rate, 18, OffboardingReason::Resignation, 0)->eosbAmount)
        ->toBe(0);

    // 3 years resigning: full accrual would be 18,000.00; one third = 6,000.00 (rounded).
    $threeYears = calculator2()->calculate(PayBasis::Salaried, $rate, 36, OffboardingReason::Resignation, 0);
    expect($threeYears->eosbAmount)->toBe(599_940);

    // 10 years resigning: full entitlement, same as a termination.
    expect(calculator2()->calculate(PayBasis::Salaried, $rate, 120, OffboardingReason::Resignation, 0)->eosbAmount)
        ->toBe(calculator2()->calculate(PayBasis::Salaried, $rate, 120, OffboardingReason::Termination, 0)->eosbAmount);
});

test('unused leave is paid at the daily rate of the working month', function () {
    // 12,000 over 20 scheduled days = 600/day; 10 days = 6,000.00
    $result = calculator2()->calculate(
        PayBasis::Salaried, 1_200_000, 0, OffboardingReason::Termination,
        unusedLeaveDays: 10, scheduledDaysInFinalMonth: 20,
    );

    expect($result->leavePayoutAmount)->toBe(600_000);
});

test('the final month is prorated by days actually served', function () {
    // 11 of 22 days = half a month.
    $result = calculator2()->calculate(
        PayBasis::Salaried, 1_200_000, 0, OffboardingReason::Termination,
        unusedLeaveDays: 0, workedDaysInFinalMonth: 11, scheduledDaysInFinalMonth: 22,
    );

    expect($result->proratedSalaryAmount)->toBe(600_000);
});

test('prorated salary never exceeds a full month', function () {
    $result = calculator2()->calculate(
        PayBasis::Salaried, 1_200_000, 0, OffboardingReason::Termination,
        unusedLeaveDays: 0, workedDaysInFinalMonth: 40, scheduledDaysInFinalMonth: 22,
    );

    expect($result->proratedSalaryAmount)->toBe(1_200_000);
});

test('deductions are stored as negative and the total is a plain sum', function () {
    $result = calculator2()->calculate(
        PayBasis::Salaried, 1_200_000, 60, OffboardingReason::Termination,
        unusedLeaveDays: 10, workedDaysInFinalMonth: 11, scheduledDaysInFinalMonth: 22,
        loanDeduction: 250_000, otherDeduction: 50_000,
    );

    expect($result->loanDeductionAmount)->toBe(-250_000)
        ->and($result->otherDeductionAmount)->toBe(-50_000)
        ->and($result->totalAmount)->toBe(
            $result->eosbAmount + $result->leavePayoutAmount + $result->proratedSalaryAmount
            + $result->loanDeductionAmount + $result->otherDeductionAmount
        );
});

test('an unpaid contract settles to zero across the board', function () {
    $result = calculator2()->calculate(
        PayBasis::Unpaid, 1_200_000, 120, OffboardingReason::Termination,
        unusedLeaveDays: 30, workedDaysInFinalMonth: 20, scheduledDaysInFinalMonth: 22,
    );

    expect($result->eosbAmount)->toBe(0)
        ->and($result->leavePayoutAmount)->toBe(0)
        ->and($result->totalAmount)->toBe(0);
});

test('service months are floored and never negative', function () {
    $calc = calculator2();

    expect($calc->serviceMonths(Carbon::parse('2024-08-01'), Carbon::parse('2026-08-15')))->toBe(24)
        ->and($calc->serviceMonths(Carbon::parse('2026-09-01'), Carbon::parse('2026-08-01')))->toBe(0)
        ->and($calc->serviceMonths(null, Carbon::parse('2026-08-01')))->toBe(0);
});

// ---------------------------------------------------------------------------
// Generation & lifecycle
// ---------------------------------------------------------------------------

test('a settlement snapshots the contract and computes every component', function () {
    [$user, $employee] = offboardingTenant();

    $settlement = app(GenerateSettlementAction::class)->handle(
        employee: $employee,
        lastWorkingDay: Carbon::parse('2026-08-31'),
        reason: OffboardingReason::Termination,
        author: $user,
    );

    expect($settlement->status)->toBe(SettlementStatus::Draft)
        ->and($settlement->employee_name)->toBe($employee->full_name)
        ->and($settlement->base_rate)->toBe(1_200_000)
        ->and($settlement->currency)->toBe('SAR')
        ->and($settlement->service_months)->toBeGreaterThan(60)
        ->and($settlement->eosb_amount)->toBeGreaterThan(0)
        ->and($settlement->total_amount)->toBe(
            $settlement->eosb_amount + $settlement->leave_payout_amount
            + $settlement->prorated_salary_amount + $settlement->loan_deduction_amount
            + $settlement->other_deduction_amount
        );
});

test('an employee cannot hold two live settlements', function () {
    [$user, $employee] = offboardingTenant();

    app(GenerateSettlementAction::class)->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    expect(fn () => app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user))
        ->toThrow(SettlementException::class, 'already has a live');
});

test('an unpriced contract is refused rather than settled at zero', function () {
    [$user, $employee] = offboardingTenant();
    EmployeeContract::query()->where('employee_id', $employee->id)->update(['base_rate' => 0]);

    expect(fn () => app(GenerateSettlementAction::class)
        ->handle($employee->refresh(), Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user))
        ->toThrow(SettlementException::class, 'no pay rate set');
});

test('an employee with no active contract cannot be settled', function () {
    [$user, $employee] = offboardingTenant();
    EmployeeContract::query()->where('employee_id', $employee->id)->update(['status' => ContractStatus::Terminated]);

    expect(fn () => app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user))
        ->toThrow(SettlementException::class, 'no active contract');
});

test('the preparer cannot approve their own settlement', function () {
    [$user, $employee] = offboardingTenant();

    $settlement = app(SubmitSettlementAction::class)->handle(
        app(GenerateSettlementAction::class)->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user)
    );

    expect(fn () => app(ApproveSettlementAction::class)->handle($settlement, $user))
        ->toThrow(SettlementException::class, 'may not also approve');
});

test('disbursing closes the employment and freezes future payroll', function () {
    // The heart of BR-606: terminating the CONTRACT is what removes the
    // employee from future runs, since PayrollRunBuilder selects by contract.
    [$user, $employee] = offboardingTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $employeeUser = User::factory()->create(['tenant_id' => $user->tenant_id, 'is_active' => true]);
    $employee->update(['user_id' => $employeeUser->id]);

    $settlement = app(ApproveSettlementAction::class)->handle(
        app(SubmitSettlementAction::class)->handle(
            app(GenerateSettlementAction::class)->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Resignation, author: $user)
        ),
        $checker
    );

    app(DisburseSettlementAction::class)->handle($settlement);

    expect($settlement->refresh()->status)->toBe(SettlementStatus::Paid)
        ->and($employee->refresh()->status)->toBe(EmployeeStatus::Resigned)
        ->and(EmployeeContract::query()->where('employee_id', $employee->id)->value('status'))
        ->toBe(ContractStatus::Terminated)
        ->and(User::query()->whereKey($employeeUser->id)->value('is_active'))->toBeFalsy();
});

test('an approved settlement is immutable except for the paid transition', function () {
    [$user, $employee] = offboardingTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $settlement = app(ApproveSettlementAction::class)->handle(
        app(SubmitSettlementAction::class)->handle(
            app(GenerateSettlementAction::class)->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user)
        ),
        $checker
    );

    expect(fn () => $settlement->update(['total_amount' => 1]))
        ->toThrow(SettlementException::class, 'can no longer be changed');

    expect(fn () => $settlement->delete())
        ->toThrow(SettlementException::class);

    // Nothing was persisted by either refusal.
    expect($settlement->fresh()->total_amount)->not->toBe(1);

    /*
     * refresh() is load-bearing here, not decoration. A model whose `updating`
     * observer throws KEEPS the rejected attribute dirty in memory — so
     * reusing this same instance would present a dirty set of
     * [total_amount, status, paid_at] to the disbursement guard, which would
     * correctly refuse it. A real request discards the instance and reloads;
     * the test has to do the same to model that honestly.
     */
    $settlement->refresh();

    // The one permitted mutation still works.
    app(DisburseSettlementAction::class)->handle($settlement);
    expect($settlement->refresh()->status)->toBe(SettlementStatus::Paid);
});

// ---------------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------------

test('the offboarding index and create pages render', function () {
    offboardingTenant();

    $this->get(route('finance.offboarding.index'))->assertOk()->assertSee('لا توجد تسويات نهاية خدمة.');
    $this->get(route('finance.offboarding.create'))->assertOk()->assertSee('إعداد تسوية نهاية خدمة');
});

test('a settlement is generated through the form with manual deductions', function () {
    [$user, $employee] = offboardingTenant();

    $this->post(route('finance.offboarding.store'), [
        'employee_id' => $employee->id,
        'last_working_day' => '2026-08-31',
        'reason' => OffboardingReason::Termination->value,
        'loan_deduction' => '1500.50',
        'other_deduction' => '0',
    ])->assertRedirect();

    $settlement = OffboardingSettlement::query()->firstOrFail();

    expect($settlement->loan_deduction_amount)->toBe(-150_050)
        ->and($settlement->status)->toBe(SettlementStatus::Draft);

    expect(session('flasher')['message'] ?? null)->toBe('تم إنشاء مسودة تسوية نهاية الخدمة.');
});

test('the show and print views render', function () {
    [$user, $employee] = offboardingTenant();

    $settlement = app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    $this->get(route('finance.offboarding.show', $settlement))->assertOk()->assertSee($employee->full_name);
    $this->get(route('finance.offboarding.print', $settlement))->assertOk()->assertSee('تسوية نهاية الخدمة');
});

test('a finance manager may prepare but not approve a settlement', function () {
    // ADR-09 applied to settlements at the route layer.
    [$user, $employee] = offboardingTenant();

    $settlement = app(SubmitSettlementAction::class)->handle(
        app(GenerateSettlementAction::class)->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user)
    );

    $financeManager = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $financeManager->assignRole(TenantPermissionCatalog::ROLE_FINANCE_MANAGER);
    $this->actingAs($financeManager);

    $this->get(route('finance.offboarding.index'))->assertOk();
    $this->post(route('finance.offboarding.approve', $settlement))->assertForbidden();
});

test('an employee cannot reach offboarding routes', function () {
    $employee = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->actingAs($employee);
    $this->get(route('finance.offboarding.index'))->assertForbidden();
    $this->get(route('finance.offboarding.create'))->assertForbidden();
});

test('settlements are invisible across tenants', function () {
    [$user, $employee] = offboardingTenant();
    $settlement = app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect(OffboardingSettlement::query()->count())->toBe(0);
    $this->get(route('finance.offboarding.show', $settlement->id))->assertNotFound();
});

test('the finance dashboard surfaces expense and offboarding metrics', function () {
    [$user, $employee] = offboardingTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $settlement = app(ApproveSettlementAction::class)->handle(
        app(SubmitSettlementAction::class)->handle(
            app(GenerateSettlementAction::class)->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user)
        ),
        $checker
    );

    $response = $this->actingAs($user)->get(route('tenant.finance.dashboard'))->assertOk();

    expect($response->viewData('offboarding')['committed'])->toBe($settlement->total_amount)
        ->and(array_keys($response->viewData('expenses')))->toContain('unpaid_claims');

    $response->assertSee('التزام نهاية الخدمة')->assertSee('المصروفات حسب التصنيف');
});
