<?php

use App\Domain\Finance\Actions\ApprovePayrollRun;
use App\Domain\Finance\Actions\MarkPayrollRunPaid;
use App\Domain\Finance\Actions\SubmitPayrollRunForApproval;
use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Enums\PayslipLineItemKind;
use App\Domain\Finance\Models\PayrollRunAdjustment;
use App\Domain\Finance\Models\PayslipLineItem;
use App\Domain\Finance\Models\PayslipLineItemType;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Notifications\Tenant\Finance\PayrollRunApprovedNotification;
use App\Notifications\Tenant\Finance\PayrollRunDisbursedNotification;
use App\Notifications\Tenant\Finance\PayrollRunSubmittedNotification;
use App\Services\Finance\PayrollRunBuilder;
use App\Services\Finance\WorkLedgerReconciler;
use Database\Seeders\PayslipLineItemTypeSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * A tenant with a reconciled ledger for the given month and one priced,
 * active contract per employee.
 *
 * @return array{0: User, 1: Employee}
 */
function financeTenant(string $role = TenantPermissionCatalog::ROLE_OWNER, string $period = '2026-08'): array
{
    $user = actingAsTenantUser($role, ['status' => 'active']);

    WorkCalendar::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Default',
        'working_days' => [0, 1, 2, 3, 4],
        'weekend_days' => [5, 6],
    ]);

    $employee = financeEmployee($user->tenant_id);

    financeReconcile($period);

    return [$user, $employee];
}

function financeEmployee(int $tenantId): Employee
{
    $employee = Employee::factory()->create(['tenant_id' => $tenantId]);

    EmployeeContract::factory()->create([
        'tenant_id' => $tenantId,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => 630_000,
        'pay_currency' => 'SAR',
        'start_date' => '2026-01-01',
        'end_date' => null,
    ]);

    return $employee;
}

function financeReconcile(string $period): void
{
    $start = Carbon::createFromFormat('Y-m-d', $period.'-01')->startOfDay();

    app(WorkLedgerReconciler::class)->reconcilePeriod($start, $start->copy()->endOfMonth()->startOfDay());
}

// ---------------------------------------------------------------------------
// 1. Dashboard redirection & role-aware navigation
// ---------------------------------------------------------------------------

test('a finance manager lands on the finance dashboard after login', function () {
    $financeManager = actingAsTenantUser(TenantPermissionCatalog::ROLE_FINANCE_MANAGER, ['status' => 'active']);

    $this->actingAs($financeManager)
        ->get(route('dashboard'))
        ->assertRedirect(route('tenant.finance.dashboard'));
});

test('the finance check precedes the employee check in the dispatcher', function () {
    // A Finance Manager also holds hr.my_dashboard.view via the self-service
    // bucket, so a broader check placed first would swallow them.
    $financeManager = actingAsTenantUser(TenantPermissionCatalog::ROLE_FINANCE_MANAGER, ['status' => 'active']);

    expect($financeManager->can('hr.my_dashboard.view'))->toBeTrue()
        ->and($financeManager->can('finance.dashboard.view'))->toBeTrue();

    $this->actingAs($financeManager)
        ->get(route('dashboard'))
        ->assertRedirect(route('tenant.finance.dashboard'));
});

test('an owner still renders the executive dashboard in place', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->actingAs($owner)->get(route('dashboard'))->assertOk();
});

test('an hr manager is unaffected by the finance branch', function () {
    $hrManager = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $this->actingAs($hrManager)
        ->get(route('dashboard'))
        ->assertRedirect(route('tenant.hr.dashboard'));
});

test('an owner sees finance collapsed into one dropdown', function () {
    [$user] = financeTenant();

    $this->actingAs($user)
        ->get(route('tenant.finance.dashboard'))
        ->assertOk()
        ->assertSee('قسم المالية');
});

test('a finance manager sees finance items flat, without the dropdown wrapper', function () {
    $financeManager = actingAsTenantUser(TenantPermissionCatalog::ROLE_FINANCE_MANAGER, ['status' => 'active']);

    $this->actingAs($financeManager)
        ->get(route('tenant.finance.dashboard'))
        ->assertOk()
        ->assertSee('المالية والرواتب')
        ->assertDontSee('قسم المالية');
});

// ---------------------------------------------------------------------------
// 2. Finance dashboard
// ---------------------------------------------------------------------------

test('the finance dashboard renders cost figures from finalized runs only', function () {
    [$user] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $draft = app(PayrollRunBuilder::class)->build('2026-08', $user);
    app(MarkPayrollRunPaid::class)->handle(
        app(ApprovePayrollRun::class)->handle(app(SubmitPayrollRunForApproval::class)->handle($draft), $checker)
    );

    $this->get(route('tenant.finance.dashboard'))
        ->assertOk()
        ->assertSee('لوحة التحكم المالية')
        ->assertSee('إجمالي المصروف');
});

test('the dashboard omits revenue tiles entirely rather than showing zero', function () {
    // BR-607 / ADR-18: a zero would read as "we earned nothing", which is a
    // different and much worse claim than "not tracked here yet".
    //
    // Asserted structurally on the view payload rather than with assertDontSee:
    // the page deliberately EXPLAINS the omission in prose, so the words
    // "الإيرادات" and "صافي الربح" legitimately appear in that banner.
    [$user] = financeTenant();

    $response = $this->get(route('tenant.finance.dashboard'))->assertOk();

    $kpiKeys = array_keys($response->viewData('kpis'));

    expect($kpiKeys)->not->toContain('total_revenue')
        ->and($kpiKeys)->not->toContain('net_profit')
        ->and($kpiKeys)->toContain('total_disbursed');

    // And the omission is communicated, not silent.
    $response->assertSee('تعرض هذه اللوحة جانب التكاليف فقط', false);
});

test('the dashboard surfaces runs awaiting approval', function () {
    [$user] = financeTenant();
    app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user));

    $this->get(route('tenant.finance.dashboard'))
        ->assertOk()
        ->assertSee('مسيرات بانتظار الاعتماد');
});

test('an employee cannot reach the finance dashboard', function () {
    $employee = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->actingAs($employee)->get(route('tenant.finance.dashboard'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// 3. Employee financial self-service
// ---------------------------------------------------------------------------

test('an employee sees only their own locked payslips in self service', function () {
    [$owner, $employee] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $employeeUser = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $employeeUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $employee->update(['user_id' => $employeeUser->id]);

    $run = app(PayrollRunBuilder::class)->build('2026-08', $owner);

    // While draft, nothing is visible.
    $this->actingAs($employeeUser)
        ->get(route('tenant.finance.my-payslips'))
        ->assertOk()
        ->assertSee('لا توجد قسائم رواتب معتمدة بعد.');

    app(ApprovePayrollRun::class)->handle(app(SubmitPayrollRunForApproval::class)->handle($run), $checker);

    $this->actingAs($employeeUser)
        ->get(route('tenant.finance.my-payslips'))
        ->assertOk()
        ->assertDontSee('لا توجد قسائم رواتب معتمدة بعد.')
        ->assertSee('2026-08');
});

test('self service shows a graceful notice when the user has no employee profile', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->actingAs($owner)
        ->get(route('tenant.finance.my-payslips'))
        ->assertOk()
        ->assertSee('لا يوجد ملف موظف مرتبط بحسابك.');
});

test('another employees payslip never appears in self service', function () {
    [$owner, $employee] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $other = financeEmployee($owner->tenant_id);
    financeReconcile('2026-08');

    $run = app(PayrollRunBuilder::class)->build('2026-08', $owner);
    app(ApprovePayrollRun::class)->handle(app(SubmitPayrollRunForApproval::class)->handle($run), $checker);

    $otherUser = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $otherUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $other->update(['user_id' => $otherUser->id]);

    $this->actingAs($otherUser)
        ->get(route('tenant.finance.my-payslips'))
        ->assertOk()
        ->assertDontSee($employee->full_name);
});

// ---------------------------------------------------------------------------
// 4. Notifications
// ---------------------------------------------------------------------------

test('submitting notifies approvers but never the maker', function () {
    Notification::fake();

    [$user] = financeTenant();
    $approver = User::factory()->create(['tenant_id' => $user->tenant_id]);
    $approver->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user));

    Notification::assertSentTo($approver, PayrollRunSubmittedNotification::class);
    Notification::assertNotSentTo($user, PayrollRunSubmittedNotification::class);
});

test('approving notifies the maker', function () {
    Notification::fake();

    [$user] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $run = app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user));
    app(ApprovePayrollRun::class)->handle($run, $checker);

    Notification::assertSentTo($user, PayrollRunApprovedNotification::class);
    Notification::assertNotSentTo($checker, PayrollRunApprovedNotification::class);
});

test('disbursing notifies each employee with their own payslip link', function () {
    Notification::fake();

    [$owner, $employee] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $employeeUser = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $employeeUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $employee->update(['user_id' => $employeeUser->id]);

    $run = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $owner)),
        $checker
    );
    app(MarkPayrollRunPaid::class)->handle($run);

    Notification::assertSentTo(
        $employeeUser,
        PayrollRunDisbursedNotification::class,
        function (PayrollRunDisbursedNotification $notification) use ($run): bool {
            // The link must land on the employee's OWN payslip (BR-614).
            return (int) $notification->payslip->payroll_run_id === (int) $run->id;
        }
    );
});

test('an employee with no linked user account is skipped silently', function () {
    Notification::fake();

    [$owner] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $run = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $owner)),
        $checker
    );

    // The seeded employee has no user_id — this must not throw.
    $paid = app(MarkPayrollRunPaid::class)->handle($run);

    expect($paid->status)->toBe(PayrollRunStatus::Paid);

    // No disbursement notice goes anywhere: there is no user to receive it.
    // (The owner DOES hold a PayrollRunApprovedNotification from the approval
    // step above — they are the maker — so a blanket assertNothingSentTo would
    // be asserting the wrong thing.)
    Notification::assertNotSentTo($owner, PayrollRunDisbursedNotification::class);
    Notification::assertNotSentTo($checker, PayrollRunDisbursedNotification::class);
});

test('the notification payload carries a title, message and resource url', function () {
    [$user] = financeTenant();
    $run = app(PayrollRunBuilder::class)->build('2026-08', $user);

    $payload = (new PayrollRunSubmittedNotification($run))->toArray($user);

    expect($payload['title'])->toBe('مسيرة رواتب بانتظار الاعتماد')
        ->and($payload['url'])->toBe(route('finance.payroll-runs.show', $run->id))
        ->and($payload['message'])->toContain('2026-08')
        ->and($payload['type'])->toBe('payroll_run_submitted');
});

// ---------------------------------------------------------------------------
// 5a. Line item types
// ---------------------------------------------------------------------------

test('line item types can be listed, created and edited', function () {
    financeTenant();

    $this->get(route('finance.line-item-types.index'))->assertOk()->assertSee('لا توجد بنود بعد.');
    $this->get(route('finance.line-item-types.create'))->assertOk();

    $this->post(route('finance.line-item-types.store'), [
        'name' => 'بدل سكن', 'code' => 'HOUSING', 'kind' => PayslipLineItemKind::Allowance->value,
        'default_amount' => '500.00', 'is_active' => 1, 'sort_order' => 5,
    ])->assertRedirect(route('finance.line-item-types.index'));

    $type = PayslipLineItemType::query()->firstOrFail();

    expect($type->default_amount)->toBe(50_000)
        ->and($type->kind)->toBe(PayslipLineItemKind::Allowance);

    $this->put(route('finance.line-item-types.update', $type), [
        'name' => 'بدل سكن', 'code' => 'HOUSING', 'kind' => PayslipLineItemKind::Deduction->value,
        'default_amount' => '500.00', 'is_active' => 1,
    ])->assertRedirect();

    // Flipping the kind must flip the stored sign.
    expect($type->refresh()->default_amount)->toBe(-50_000);
});

test('a deduction type stores a negative default even when submitted positive', function () {
    financeTenant();

    $this->post(route('finance.line-item-types.store'), [
        'name' => 'تأمينات', 'kind' => PayslipLineItemKind::Deduction->value, 'default_amount' => '250.75',
    ])->assertRedirect();

    expect(PayslipLineItemType::query()->value('default_amount'))->toBe(-25_075);
});

test('line item type codes are unique per tenant', function () {
    financeTenant();

    $payload = ['name' => 'بدل', 'code' => 'DUP', 'kind' => PayslipLineItemKind::Allowance->value, 'default_amount' => '10'];

    $this->post(route('finance.line-item-types.store'), $payload)->assertRedirect();
    $this->post(route('finance.line-item-types.store'), $payload)->assertSessionHasErrors('code');
});

test('a finance manager may manage line item types but an employee may not', function () {
    financeTenant();

    $employee = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);
    $this->actingAs($employee)->get(route('finance.line-item-types.index'))->assertForbidden();
});

test('the seeder ships starter types inactive and unpriced', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->seed(PayslipLineItemTypeSeeder::class);

    app(TenantContext::class)->setTenant(
        Tenant::query()->find($user->tenant_id)
    );

    $types = PayslipLineItemType::query()->get();

    expect($types)->toHaveCount(5)
        ->and($types->every(fn ($type): bool => $type->is_active === false))->toBeTrue()
        ->and($types->every(fn ($type): bool => $type->default_amount === 0))->toBeTrue();
});

// ---------------------------------------------------------------------------
// 5b. Adjustments — the BR-603 correction path
// ---------------------------------------------------------------------------

test('an adjustment corrects a locked run by paying it on a later draft', function () {
    [$user, $employee] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    // August: approved and locked.
    $august = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user)),
        $checker
    );
    $augustPayslip = $august->payslips()->first();

    // September: a fresh draft to carry the correction.
    financeReconcile('2026-09');
    $september = app(PayrollRunBuilder::class)->build('2026-09', $user);
    $netBefore = $september->payslips()->first()->net_amount;

    $this->post(route('finance.payroll-runs.adjustments.store', $september), [
        'original_payslip_id' => $augustPayslip->id,
        'amount' => '-125.50',
        'reason' => 'استرداد بدل زائد',
    ])->assertRedirect(route('finance.payroll-runs.show', $september));

    $adjustment = PayrollRunAdjustment::query()->firstOrFail();

    expect($adjustment->amount)->toBe(-12_550)
        ->and($adjustment->original_period)->toBe('2026-08')
        ->and($adjustment->isClawback())->toBeTrue();

    // The correction actually moves money on the carrying run...
    expect($september->payslips()->first()->net_amount)->toBe($netBefore - 12_550);

    // ...and August is untouched.
    expect($august->refresh()->status)->toBe(PayrollRunStatus::Approved)
        ->and($augustPayslip->refresh()->net_amount)->toBe($augustPayslip->net_amount);
});

test('an adjustment writes a labelled line item on the carrying payslip', function () {
    [$user] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $august = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user)),
        $checker
    );

    financeReconcile('2026-09');
    $september = app(PayrollRunBuilder::class)->build('2026-09', $user);

    $this->post(route('finance.payroll-runs.adjustments.store', $september), [
        'original_payslip_id' => $august->payslips()->first()->id,
        'amount' => '300',
        'reason' => 'بدل لم يُحتسب',
    ])->assertRedirect();

    $line = PayslipLineItem::query()->where('label', 'like', 'تسوية%')->firstOrFail();

    expect($line->amount)->toBe(30_000)
        ->and($line->kind)->toBe(PayslipLineItemKind::Allowance)
        ->and($line->label)->toContain('2026-08');
});

test('a run cannot adjust itself and an unlocked payslip cannot be adjusted', function () {
    [$user] = financeTenant();

    $draft = app(PayrollRunBuilder::class)->build('2026-08', $user);

    $this->from(route('finance.payroll-runs.show', $draft))
        ->post(route('finance.payroll-runs.adjustments.store', $draft), [
            'original_payslip_id' => $draft->payslips()->first()->id,
            'amount' => '100',
            'reason' => 'محاولة غير صالحة',
        ])->assertRedirect(route('finance.payroll-runs.show', $draft));

    expect(PayrollRunAdjustment::query()->count())->toBe(0);
});

test('an adjustment cannot be carried by a locked run', function () {
    [$user] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $august = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user)),
        $checker
    );

    $this->from(route('finance.payroll-runs.show', $august))
        ->post(route('finance.payroll-runs.adjustments.store', $august), [
            'original_payslip_id' => $august->payslips()->first()->id,
            'amount' => '100',
            'reason' => 'محاولة على مسيرة مقفلة',
        ])->assertRedirect();

    expect(PayrollRunAdjustment::query()->count())->toBe(0);
});

test('a zero adjustment and a missing reason are both rejected', function () {
    [$user] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $august = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user)),
        $checker
    );
    financeReconcile('2026-09');
    $september = app(PayrollRunBuilder::class)->build('2026-09', $user);
    $payslipId = $august->payslips()->first()->id;

    $this->post(route('finance.payroll-runs.adjustments.store', $september), [
        'original_payslip_id' => $payslipId, 'amount' => '0', 'reason' => 'صفر',
    ])->assertSessionHasErrors('amount');

    $this->post(route('finance.payroll-runs.adjustments.store', $september), [
        'original_payslip_id' => $payslipId, 'amount' => '50', 'reason' => '',
    ])->assertSessionHasErrors('reason');

    expect(PayrollRunAdjustment::query()->count())->toBe(0);
});

test('adjustments are invisible across tenants', function () {
    [$user] = financeTenant();
    $checker = User::factory()->create(['tenant_id' => $user->tenant_id]);

    $august = app(ApprovePayrollRun::class)->handle(
        app(SubmitPayrollRunForApproval::class)->handle(app(PayrollRunBuilder::class)->build('2026-08', $user)),
        $checker
    );
    financeReconcile('2026-09');
    $september = app(PayrollRunBuilder::class)->build('2026-09', $user);

    $this->post(route('finance.payroll-runs.adjustments.store', $september), [
        'original_payslip_id' => $august->payslips()->first()->id,
        'amount' => '75', 'reason' => 'تسوية',
    ]);

    expect(PayrollRunAdjustment::query()->count())->toBe(1);

    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect(PayrollRunAdjustment::query()->count())->toBe(0);
});
