<?php

use App\Domain\Finance\Actions\ApprovePayrollRun;
use App\Domain\Finance\Actions\SubmitPayrollRunForApproval;
use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItem;
use App\Domain\Finance\Models\PayslipLineItemType;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Services\Finance\PayrollRunBuilder;
use App\Services\Finance\WorkLedgerReconciler;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

/**
 * A tenant with a reconciled August ledger and one priced, active contract.
 *
 * @return array{0: User, 1: Employee}
 */
function payrollHttpTenant(string $role = TenantPermissionCatalog::ROLE_OWNER): array
{
    $user = actingAsTenantUser($role, ['status' => 'active']);

    WorkCalendar::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Default',
        'working_days' => [0, 1, 2, 3, 4],
        'weekend_days' => [5, 6],
    ]);

    $employee = Employee::factory()->create(['tenant_id' => $user->tenant_id]);

    EmployeeContract::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => 630_000,
        'pay_currency' => 'SAR',
        'start_date' => '2026-01-01',
        'end_date' => null,
    ]);

    app(WorkLedgerReconciler::class)->reconcilePeriod(
        Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31')
    );

    return [$user, $employee];
}

function buildRunFor(User $user): PayrollRun
{
    return app(PayrollRunBuilder::class)->build('2026-08', $user);
}

// ---------------------------------------------------------------------------
// Index / create / store
// ---------------------------------------------------------------------------

test('the payroll index renders for a user who may view payroll', function () {
    [$user] = payrollHttpTenant();
    buildRunFor($user);

    $this->get(route('finance.payroll-runs.index'))
        ->assertOk()
        ->assertSee('مسيرات الرواتب')
        ->assertSee('2026-08');
});

test('the index shows an empty state before any run exists', function () {
    payrollHttpTenant();

    $this->get(route('finance.payroll-runs.index'))
        ->assertOk()
        ->assertSee('لا توجد مسيرات رواتب بعد.');
});

test('the index filters by status', function () {
    [$user] = payrollHttpTenant();
    buildRunFor($user);

    $this->get(route('finance.payroll-runs.index', ['status' => PayrollRunStatus::Paid->value]))
        ->assertOk()
        ->assertSee('لا توجد مسيرات رواتب بعد.');

    $this->get(route('finance.payroll-runs.index', ['status' => PayrollRunStatus::Draft->value]))
        ->assertOk()
        ->assertSee('2026-08');
});

test('the create form renders', function () {
    payrollHttpTenant();

    $this->get(route('finance.payroll-runs.create'))
        ->assertOk()
        ->assertSee('إنشاء مسيرة رواتب');
});

test('storing a run builds a draft and flashes success', function () {
    [$user] = payrollHttpTenant();

    $response = $this->post(route('finance.payroll-runs.store'), ['period' => '2026-08']);

    $run = PayrollRun::query()->first();

    $response->assertRedirect(route('finance.payroll-runs.show', $run));

    expect($run->status)->toBe(PayrollRunStatus::Draft)
        ->and($run->maker_id)->toBe($user->id)
        ->and($run->payslips()->count())->toBe(1);

    // Flasher toast (the project's standard feedback channel).
    expect(session('flasher'))->toBeArray()
        ->and(session('flasher')['message'] ?? null)->toBe('تم إنشاء مسودة مسيرة الرواتب بنجاح.');
});

test('a malformed period is rejected by validation', function () {
    payrollHttpTenant();

    $this->post(route('finance.payroll-runs.store'), ['period' => 'august'])
        ->assertSessionHasErrors('period');

    expect(PayrollRun::query()->count())->toBe(0);
});

test('a domain refusal becomes an error toast rather than a 500', function () {
    // No ledger reconciled for September — the empty-ledger guard fires.
    payrollHttpTenant();

    $this->from(route('finance.payroll-runs.create'))
        ->post(route('finance.payroll-runs.store'), ['period' => '2026-09'])
        ->assertRedirect(route('finance.payroll-runs.create'));

    expect(PayrollRun::query()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Show / edit / update
// ---------------------------------------------------------------------------

test('the show page renders the run with its payslips', function () {
    [$user, $employee] = payrollHttpTenant();
    $run = buildRunFor($user);

    $this->get(route('finance.payroll-runs.show', $run))
        ->assertOk()
        ->assertSee($employee->full_name)
        ->assertSee('صافي المستحق');
});

test('the edit form renders for a draft and updates notes', function () {
    [$user] = payrollHttpTenant();
    $run = buildRunFor($user);

    $this->get(route('finance.payroll-runs.edit', $run))->assertOk();

    $this->put(route('finance.payroll-runs.update', $run), ['notes' => 'مراجعة أولية'])
        ->assertRedirect(route('finance.payroll-runs.show', $run));

    expect($run->refresh()->notes)->toBe('مراجعة أولية');
});

test('editing a line item converts major units to minor and keeps the sign', function () {
    [$user] = payrollHttpTenant();

    PayslipLineItemType::create([
        'name' => 'تأمينات', 'kind' => PayslipLineItemKind::Deduction, 'default_amount' => 20_000,
    ]);

    $run = buildRunFor($user);
    $lineItem = PayslipLineItem::query()->firstOrFail();

    // 350.50 major units submitted as a positive magnitude on a deduction line.
    $this->put(route('finance.payroll-runs.update', $run), [
        'line_items' => [$lineItem->id => '350.50'],
    ])->assertRedirect();

    expect($lineItem->refresh()->amount)->toBe(-35_050);

    $payslip = $run->payslips()->first();
    expect($payslip->deductions_total)->toBe(-35_050)
        ->and($payslip->net_amount)->toBe($payslip->gross_amount + $payslip->absence_deduction - 35_050);
});

test('a locked run cannot be edited through the UI', function () {
    [$user] = payrollHttpTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(buildRunFor($user)),
        $checker
    );

    $this->get(route('finance.payroll-runs.edit', $run))->assertForbidden();
    $this->put(route('finance.payroll-runs.update', $run), ['notes' => 'x'])->assertForbidden();
});

// ---------------------------------------------------------------------------
// Transitions
// ---------------------------------------------------------------------------

test('a run can be submitted, approved by another user, and disbursed', function () {
    [$user] = payrollHttpTenant();
    $run = buildRunFor($user);

    $this->post(route('finance.payroll-runs.submit', $run))
        ->assertRedirect(route('finance.payroll-runs.show', $run));
    expect($run->refresh()->status)->toBe(PayrollRunStatus::PendingApproval);

    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $checker->assignRole(TenantPermissionCatalog::ROLE_OWNER);
    $this->actingAs($checker);

    $this->post(route('finance.payroll-runs.approve', $run))->assertRedirect();
    expect($run->refresh()->status)->toBe(PayrollRunStatus::Approved);

    $this->post(route('finance.payroll-runs.disburse', $run))->assertRedirect();
    expect($run->refresh()->status)->toBe(PayrollRunStatus::Paid);
});

test('the maker is refused when approving their own run', function () {
    // BR-615 surfaced through HTTP: the request is authorized by permission but
    // refused by the domain, and comes back as an error toast, not a 500.
    [$user] = payrollHttpTenant();
    $run = app(SubmitPayrollRunForApproval::class)->handle(buildRunFor($user));

    $this->from(route('finance.payroll-runs.show', $run))
        ->post(route('finance.payroll-runs.approve', $run))
        ->assertRedirect(route('finance.payroll-runs.show', $run));

    expect($run->refresh()->status)->toBe(PayrollRunStatus::PendingApproval);
});

test('rejecting requires a reason and returns the run to draft', function () {
    [$user] = payrollHttpTenant();
    $run = app(SubmitPayrollRunForApproval::class)->handle(buildRunFor($user));

    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $checker->assignRole(TenantPermissionCatalog::ROLE_OWNER);
    $this->actingAs($checker);

    $this->post(route('finance.payroll-runs.reject', $run), [])
        ->assertSessionHasErrors('rejection_reason');

    $this->post(route('finance.payroll-runs.reject', $run), ['rejection_reason' => 'بدل خاطئ'])
        ->assertRedirect();

    expect($run->refresh()->status)->toBe(PayrollRunStatus::Draft)
        ->and($run->rejection_reason)->toBe('بدل خاطئ');
});

test('recalculate refreshes run totals from its payslips', function () {
    [$user] = payrollHttpTenant();
    $run = buildRunFor($user);

    $run->payslips()->first()->update(['net_amount' => 12_345]);

    $this->post(route('finance.payroll-runs.recalculate', $run))->assertRedirect();

    expect($run->refresh()->total_net)->toBe(12_345);
});

// ---------------------------------------------------------------------------
// Soft delete via the shared trash console
// ---------------------------------------------------------------------------

test('deleting a draft soft deletes it and it appears in the shared trash', function () {
    [$user] = payrollHttpTenant();
    $run = buildRunFor($user);

    $this->delete(route('finance.payroll-runs.destroy', $run))
        ->assertRedirect(route('finance.payroll-runs.index'));

    expect(PayrollRun::query()->count())->toBe(0)
        ->and(PayrollRun::withTrashed()->count())->toBe(1);

    $this->get(route('tenant.trash.index', ['type' => 'payroll-runs']))
        ->assertOk()
        ->assertSee('مسيرة رواتب 2026-08');
});

test('a trashed draft restores through the shared trash console', function () {
    [$user] = payrollHttpTenant();
    $run = buildRunFor($user);
    $this->delete(route('finance.payroll-runs.destroy', $run));

    $this->post(route('tenant.trash.restore', ['type' => 'payroll-runs', 'id' => $run->id]))
        ->assertRedirect();

    expect(PayrollRun::query()->count())->toBe(1);
});

test('an approved run cannot be deleted through the UI', function () {
    [$user] = payrollHttpTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(buildRunFor($user)),
        $checker
    );

    $this->from(route('finance.payroll-runs.index'))
        ->delete(route('finance.payroll-runs.destroy', $run))
        ->assertRedirect(route('finance.payroll-runs.index'));

    expect(PayrollRun::query()->count())->toBe(1)
        ->and(PayrollRun::withTrashed()->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// Payslip visibility (BR-614)
// ---------------------------------------------------------------------------

test('the payslip detail and print views render for finance staff', function () {
    [$user] = payrollHttpTenant();
    $run = buildRunFor($user);
    $payslip = $run->payslips()->first();

    $this->get(route('finance.payslips.show', $payslip))
        ->assertOk()
        ->assertSee($payslip->employee_name);

    $this->get(route('finance.payslips.print', $payslip))
        ->assertOk()
        ->assertSee('قسيمة راتب');
});

test('an employee sees only their own approved payslip', function () {
    [$owner, $employee] = payrollHttpTenant();
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $run = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(buildRunFor($owner)),
        $checker
    );
    $payslip = $run->payslips()->first();

    $employeeUser = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $employeeUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $employee->update(['user_id' => $employeeUser->id]);

    $this->actingAs($employeeUser)
        ->get(route('finance.payslips.show', $payslip))
        ->assertOk();
});

test('an employee cannot read another employees payslip', function () {
    [$owner] = payrollHttpTenant();
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $run = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(buildRunFor($owner)),
        $checker
    );
    $payslip = $run->payslips()->first();

    // A different employee entirely.
    $otherUser = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $otherUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    Employee::factory()->create(['tenant_id' => $owner->tenant_id, 'user_id' => $otherUser->id]);

    $this->actingAs($otherUser)
        ->get(route('finance.payslips.show', $payslip))
        ->assertForbidden();
});

test('an employee cannot read a draft payslip even if it is their own', function () {
    // BR-614: draft figures are the preparer's working copy.
    [$owner, $employee] = payrollHttpTenant();
    $run = buildRunFor($owner);
    $payslip = $run->payslips()->first();

    $employeeUser = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $employeeUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $employee->update(['user_id' => $employeeUser->id]);

    $this->actingAs($employeeUser)
        ->get(route('finance.payslips.show', $payslip))
        ->assertForbidden();
});

// ---------------------------------------------------------------------------
// Authorization
// ---------------------------------------------------------------------------

test('an employee cannot reach any payroll run route', function () {
    payrollHttpTenant();
    $employeeUser = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->actingAs($employeeUser);

    $this->get(route('finance.payroll-runs.index'))->assertForbidden();
    $this->get(route('finance.payroll-runs.create'))->assertForbidden();
    $this->post(route('finance.payroll-runs.store'), ['period' => '2026-08'])->assertForbidden();
});

test('a finance manager may prepare but not approve over http', function () {
    // ADR-09 enforced at the route layer, not just in the domain.
    [$user] = payrollHttpTenant();
    $run = app(SubmitPayrollRunForApproval::class)->handle(buildRunFor($user));

    $financeManager = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $financeManager->assignRole(TenantPermissionCatalog::ROLE_FINANCE_MANAGER);
    $this->actingAs($financeManager);

    $this->get(route('finance.payroll-runs.index'))->assertOk();
    $this->post(route('finance.payroll-runs.approve', $run))->assertForbidden();
});

test('payroll runs from another tenant are not reachable', function () {
    [$user] = payrollHttpTenant();
    $run = buildRunFor($user);

    // A fresh tenant/owner: the global scope must hide the other tenant's run.
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('finance.payroll-runs.show', $run->id))->assertNotFound();

    // Assert the empty state rather than absence of "2026-08": the filter
    // input carries that string as a placeholder, so assertDontSee would fail
    // on the page's own chrome rather than on leaked data.
    $this->get(route('finance.payroll-runs.index'))
        ->assertOk()
        ->assertSee('لا توجد مسيرات رواتب بعد.');
});

test('payslips from another tenant are not reachable', function () {
    [$user] = payrollHttpTenant();
    buildRunFor($user);
    $payslipId = Payslip::query()->value('id');

    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('finance.payslips.show', $payslipId))->assertNotFound();
});
