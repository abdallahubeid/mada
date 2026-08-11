<?php

use App\Domain\Finance\Actions\GenerateSettlementAction;
use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Finance\Models\FinanceSetting;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Finance\Support\EosbPolicy;
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
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function eosbCalculator(): OffboardingCalculator
{
    return new OffboardingCalculator;
}

/**
 * @return array{0: User, 1: Employee}
 */
function financeSettingsTenant(string $role = TenantPermissionCatalog::ROLE_OWNER): array
{
    $user = actingAsTenantUser($role, ['status' => 'active']);

    WorkCalendar::create([
        'tenant_id' => $user->tenant_id,
        'name' => 'Default',
        'working_days' => [0, 1, 2, 3, 4],
        'weekend_days' => [5, 6],
    ]);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'joining_date' => '2020-08-01',
        'status' => EmployeeStatus::Active,
    ]);

    EmployeeContract::factory()->create([
        'tenant_id' => $user->tenant_id,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => 1_200_000,
        'pay_currency' => 'SAR',
        'start_date' => '2020-08-01',
        'end_date' => null,
    ]);

    app(WorkLedgerReconciler::class)->reconcilePeriod(
        Carbon::parse('2026-08-01'), Carbon::parse('2026-08-31')
    );

    return [$user, $employee];
}

// ---------------------------------------------------------------------------
// The defaults ARE the old constants — the refactor must change no number
// ---------------------------------------------------------------------------

test('the default policy reproduces the constants it replaced', function () {
    $policy = EosbPolicy::default();

    expect($policy->enabled)->toBeTrue()
        ->and($policy->tierBoundaryMonths)->toBe(60)
        ->and($policy->lowerTierBps)->toBe(5_000)
        ->and($policy->upperTierBps)->toBe(10_000)
        ->and($policy->nominalMonthDays)->toBe(22)
        ->and($policy->nominalDayHours)->toBe(8)
        ->and($policy->resignationTaper)->toBe([
            ['months' => 0, 'bps' => 0],
            ['months' => 24, 'bps' => 3_333],
            ['months' => 60, 'bps' => 6_667],
            ['months' => 120, 'bps' => 10_000],
        ]);
});

test('omitting a policy computes exactly what the hardcoded version did', function () {
    // Same three fixtures the pre-refactor calculator tests assert on.
    expect(eosbCalculator()->calculate(PayBasis::Salaried, 1_200_000, 60, OffboardingReason::Termination, 0)->eosbAmount)
        ->toBe(3_000_000)
        ->and(eosbCalculator()->calculate(PayBasis::Salaried, 1_200_000, 120, OffboardingReason::Termination, 0)->eosbAmount)
        ->toBe(9_000_000)
        ->and(eosbCalculator()->calculate(PayBasis::Salaried, 1_200_000, 36, OffboardingReason::Resignation, 0)->eosbAmount)
        ->toBe(599_940);
});

test('a tenant with no settings row resolves to the defaults without creating one', function () {
    financeSettingsTenant();

    $settings = FinanceSetting::current();

    expect($settings->exists)->toBeFalse()
        ->and($settings->eosbPolicy()->toArray())->toBe(EosbPolicy::default()->toArray())
        ->and(FinanceSetting::query()->count())->toBe(0);
});

// ---------------------------------------------------------------------------
// Dynamic rules actually drive the arithmetic
// ---------------------------------------------------------------------------

test('configured tier rates and boundary change the entitlement', function () {
    // Boundary at 24 months, 25% below it and 200% above.
    // 24 months @ 25%: 12,000 * 24/12 * 0.25 =  6,000.00
    // 12 months @ 200%: 12,000 * 12/12 * 2.00 = 24,000.00
    $policy = new EosbPolicy(
        enabled: true,
        tierBoundaryMonths: 24,
        lowerTierBps: 2_500,
        upperTierBps: 20_000,
        resignationTaper: EosbPolicy::defaultResignationTaper(),
        nominalMonthDays: 22,
        nominalDayHours: 8,
    );

    $result = eosbCalculator()->calculate(
        PayBasis::Salaried, 1_200_000, 36, OffboardingReason::Termination, 0, policy: $policy
    );

    expect($result->eosbAmount)->toBe(3_000_000);
});

test('disabling eosb zeroes the benefit without touching the rest of the settlement', function () {
    $policy = EosbPolicy::fromArray([...EosbPolicy::default()->toArray(), 'enabled' => false]);

    $result = eosbCalculator()->calculate(
        PayBasis::Salaried, 1_200_000, 120, OffboardingReason::Termination,
        unusedLeaveDays: 10, workedDaysInFinalMonth: 11, scheduledDaysInFinalMonth: 20,
        policy: $policy,
    );

    // Leave payout and prorated salary are contractual, not statutory — they
    // must survive a tenant switching the statutory benefit off.
    expect($result->eosbAmount)->toBe(0)
        ->and($result->leavePayoutAmount)->toBe(600_000)
        ->and($result->proratedSalaryAmount)->toBe(660_000)
        ->and($result->totalAmount)->toBe(1_260_000);
});

test('a configured resignation taper replaces the default bands', function () {
    // Half the entitlement from one year of service — the default pays nothing
    // below two years, so this can only pass if the configured taper is read.
    $policy = EosbPolicy::fromArray([
        ...EosbPolicy::default()->toArray(),
        'resignation_taper' => [
            ['months' => 0, 'bps' => 0],
            ['months' => 12, 'bps' => 5_000],
        ],
    ]);

    $result = eosbCalculator()->calculate(
        PayBasis::Salaried, 1_200_000, 18, OffboardingReason::Resignation, 0, policy: $policy
    );

    expect($result->eosbAmount)->toBe(450_000)
        ->and(eosbCalculator()->calculate(PayBasis::Salaried, 1_200_000, 18, OffboardingReason::Resignation, 0)->eosbAmount)
        ->toBe(0);
});

test('taper bands are sorted before they are read', function () {
    /*
     * resignationTaperBps() takes the LAST matching band, so an unsorted array
     * silently pays the wrong rate: iterating [24 => 33%, 0 => 0%] against 30
     * months matches both and ends on 0%. Normalization is what prevents a
     * settings screen row order from deciding an entitlement.
     */
    $policy = EosbPolicy::fromArray([
        ...EosbPolicy::default()->toArray(),
        'resignation_taper' => [
            ['months' => 24, 'bps' => 3_333],
            ['months' => 0, 'bps' => 0],
        ],
    ]);

    expect($policy->resignationTaper[0]['months'])->toBe(0)
        ->and($policy->resignationTaperBps(30))->toBe(3_333);
});

test('the nominal month drives an hourly employee wage and the leave payout fallback', function () {
    $policy = EosbPolicy::fromArray([
        ...EosbPolicy::default()->toArray(),
        'nominal_month_days' => 20,
    ]);

    // Hourly at 100.00/hr → 100 * 8h * 20d = 16,000.00 notional month.
    // 12 months at the default 50% = 16,000 * 1 * 0.5 = 8,000.00
    $hourly = eosbCalculator()->calculate(
        PayBasis::Hourly, 10_000, 12, OffboardingReason::Termination, 0, policy: $policy
    );

    // With no scheduled days in the final month the payout divides by the
    // nominal month instead: 12,000 * 5 / 20 = 3,000.00 (3,272.72 at 22 days).
    $leave = eosbCalculator()->calculate(
        PayBasis::Salaried, 1_200_000, 0, OffboardingReason::Termination,
        unusedLeaveDays: 5, scheduledDaysInFinalMonth: 0, policy: $policy,
    );

    expect($hourly->eosbAmount)->toBe(800_000)
        ->and($leave->leavePayoutAmount)->toBe(300_000)
        ->and(eosbCalculator()->calculate(
            PayBasis::Salaried, 1_200_000, 0, OffboardingReason::Termination,
            unusedLeaveDays: 5, scheduledDaysInFinalMonth: 0,
        )->leavePayoutAmount)->toBe(272_727);
});

test('rounding stays exact half-up integer arithmetic under custom rates', function () {
    // 33.33% of a 12,000.00 month over 36 months at 50% accrual.
    $policy = EosbPolicy::fromArray([
        ...EosbPolicy::default()->toArray(),
        'resignation_taper' => [['months' => 0, 'bps' => 3_333]],
    ]);

    $result = eosbCalculator()->calculate(
        PayBasis::Salaried, 1_200_000, 36, OffboardingReason::Resignation, 0, policy: $policy
    );

    expect($result->eosbAmount)->toBe(599_940)->toBeInt();
});

// ---------------------------------------------------------------------------
// Generation reads the tenant's settings, and freezes them
// ---------------------------------------------------------------------------

test('a settlement is computed with the tenant configured rules', function () {
    [$user, $employee] = financeSettingsTenant();

    $withDefaults = app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);
    $defaultEosb = $withDefaults->eosb_amount;
    $withDefaults->forceDelete();

    FinanceSetting::query()->create([
        'tenant_id' => $user->tenant_id,
        'eosb_enabled' => false,
    ]);

    $settlement = app(GenerateSettlementAction::class)
        ->handle($employee->refresh(), Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    expect($defaultEosb)->toBeGreaterThan(0)
        ->and($settlement->eosb_amount)->toBe(0);
});

test('a settlement snapshots the rules it was computed under', function () {
    [$user, $employee] = financeSettingsTenant();

    FinanceSetting::query()->create([
        'tenant_id' => $user->tenant_id,
        'eosb_tier_boundary_months' => 24,
        'eosb_lower_tier_bps' => 2_500,
        'eosb_upper_tier_bps' => 20_000,
    ]);

    $settlement = app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    $amountAtGeneration = $settlement->eosb_amount;

    // Somebody edits the rules afterwards.
    FinanceSetting::query()->first()->update([
        'eosb_lower_tier_bps' => 9_000,
        'eosb_upper_tier_bps' => 9_000,
    ]);

    $settlement->refresh();

    expect($settlement->eosbPolicy()->lowerTierBps)->toBe(2_500)
        ->and($settlement->eosbPolicy()->upperTierBps)->toBe(20_000)
        ->and($settlement->eosbPolicy()->tierBoundaryMonths)->toBe(24)
        ->and($settlement->eosb_amount)->toBe($amountAtGeneration);
});

test('a settlement predating the snapshot column reads back as the defaults', function () {
    [$user, $employee] = financeSettingsTenant();

    $settlement = app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    /*
     * Query builder on purpose: this simulates a row written before the column
     * existed, and Builder::update() fires no model events, so the settlement
     * observer's lock guard is correctly not involved.
     */
    DB::table('offboarding_settlements')->where('id', $settlement->id)->update(['eosb_policy' => null]);

    expect($settlement->refresh()->eosbPolicy()->toArray())->toBe(EosbPolicy::default()->toArray());
});

test('the settlement page shows the rules the settlement was computed under', function () {
    [$user, $employee] = financeSettingsTenant();

    $settlement = app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    $this->get(route('finance.offboarding.show', $settlement))
        ->assertOk()
        ->assertSee('قواعد نهاية الخدمة المطبَّقة على هذه التسوية');
});

// ---------------------------------------------------------------------------
// HTTP — access control
// ---------------------------------------------------------------------------

test('the owner can open the finance settings screen', function () {
    financeSettingsTenant();

    $this->get(route('finance.settings.edit'))
        ->assertOk()
        ->assertSee('إعدادات المالية ونهاية الخدمة')
        ->assertSee('هذه القيم ليست استشارة قانونية');
});

test('a finance manager can open and save the finance settings screen', function () {
    [$owner] = financeSettingsTenant();

    $financeManager = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $financeManager->assignRole(TenantPermissionCatalog::ROLE_FINANCE_MANAGER);
    $this->actingAs($financeManager);

    $this->get(route('finance.settings.edit'))->assertOk();

    $this->put(route('finance.settings.update'), [
        'eosb_enabled' => '1',
        'eosb_tier_boundary_months' => 36,
        'eosb_lower_tier_percent' => '50',
        'eosb_upper_tier_percent' => '100',
        'eosb_resignation_taper' => [['months' => 0, 'percent' => '0']],
        'nominal_month_days' => 22,
        'nominal_day_hours' => 8,
    ])->assertRedirect(route('finance.settings.edit'));

    expect(FinanceSetting::query()->value('eosb_tier_boundary_months'))->toBe(36);
});

test('an hr manager cannot reach the finance settings screen', function () {
    financeSettingsTenant(TenantPermissionCatalog::ROLE_HR_MANAGER);

    $this->get(route('finance.settings.edit'))->assertForbidden();
    $this->put(route('finance.settings.update'), [])->assertForbidden();
    $this->post(route('finance.settings.reset'))->assertForbidden();
});

test('an employee cannot reach the finance settings screen', function () {
    financeSettingsTenant(TenantPermissionCatalog::ROLE_EMPLOYEE);

    $this->get(route('finance.settings.edit'))->assertForbidden();
    $this->put(route('finance.settings.update'), [])->assertForbidden();
});

// ---------------------------------------------------------------------------
// HTTP — persistence, conversion, validation
// ---------------------------------------------------------------------------

test('percentages from the form are stored as integer basis points', function () {
    financeSettingsTenant();

    $this->put(route('finance.settings.update'), [
        'eosb_enabled' => '1',
        'eosb_tier_boundary_months' => 24,
        'eosb_lower_tier_percent' => '25.5',
        'eosb_upper_tier_percent' => '200',
        'eosb_resignation_taper' => [
            ['months' => 12, 'percent' => '33.33'],
            ['months' => 0, 'percent' => '0'],
        ],
        'nominal_month_days' => 20,
        'nominal_day_hours' => 7,
    ])->assertRedirect(route('finance.settings.edit'));

    $settings = FinanceSetting::query()->firstOrFail();

    expect($settings->eosb_lower_tier_bps)->toBe(2_550)
        ->and($settings->eosb_upper_tier_bps)->toBe(20_000)
        ->and($settings->nominal_month_days)->toBe(20)
        ->and($settings->nominal_day_hours)->toBe(7)
        // Stored ascending, whatever order the rows were submitted in.
        ->and($settings->eosb_resignation_taper)->toBe([
            ['months' => 0, 'bps' => 0],
            ['months' => 12, 'bps' => 3_333],
        ]);
});

test('an unchecked enable box disables the benefit rather than being ignored', function () {
    financeSettingsTenant();

    $this->put(route('finance.settings.update'), [
        'eosb_tier_boundary_months' => 60,
        'eosb_lower_tier_percent' => '50',
        'eosb_upper_tier_percent' => '100',
        'nominal_month_days' => 22,
        'nominal_day_hours' => 8,
    ])->assertRedirect();

    expect(FinanceSetting::query()->value('eosb_enabled'))->toBeFalsy();
});

test('out of range and over precise rules are refused', function () {
    financeSettingsTenant();

    $this->put(route('finance.settings.update'), [
        'eosb_enabled' => '1',
        'eosb_tier_boundary_months' => 9999,
        'eosb_lower_tier_percent' => '25.555',
        'eosb_upper_tier_percent' => '400',
        'eosb_resignation_taper' => [['months' => 5, 'percent' => '150']],
        'nominal_month_days' => 0,
        'nominal_day_hours' => 99,
    ])->assertSessionHasErrors([
        'eosb_tier_boundary_months',
        'eosb_lower_tier_percent',
        'eosb_upper_tier_percent',
        'eosb_resignation_taper.0.percent',
        'nominal_month_days',
        'nominal_day_hours',
    ]);

    expect(FinanceSetting::query()->count())->toBe(0);
});

test('saving is audited in the units the settlement snapshot uses', function () {
    financeSettingsTenant();

    $this->put(route('finance.settings.update'), [
        'eosb_enabled' => '1',
        'eosb_tier_boundary_months' => 60,
        'eosb_lower_tier_percent' => '50',
        'eosb_upper_tier_percent' => '100',
        'eosb_resignation_taper' => [['months' => 0, 'percent' => '0']],
        'nominal_month_days' => 22,
        'nominal_day_hours' => 8,
    ])->assertRedirect();

    $log = DB::table('audit_logs')->where('action', 'finance_settings.updated')->first();

    expect($log)->not->toBeNull()
        ->and(json_decode((string) $log->changes, true)['eosb_lower_tier_bps'])->toBe(5_000);
});

test('resetting restores the shipped defaults', function () {
    financeSettingsTenant();

    FinanceSetting::query()->create([
        'tenant_id' => auth()->user()->tenant_id,
        'eosb_enabled' => false,
        'eosb_tier_boundary_months' => 12,
        'eosb_lower_tier_bps' => 100,
        'eosb_upper_tier_bps' => 200,
        'nominal_month_days' => 15,
    ]);

    $this->post(route('finance.settings.reset'))->assertRedirect(route('finance.settings.edit'));

    expect(FinanceSetting::query()->firstOrFail()->eosbPolicy()->toArray())
        ->toBe(EosbPolicy::default()->toArray());
});

test('finance settings are invisible across tenants', function () {
    [$user] = financeSettingsTenant();

    FinanceSetting::query()->create([
        'tenant_id' => $user->tenant_id,
        'eosb_tier_boundary_months' => 12,
    ]);

    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect(FinanceSetting::query()->count())->toBe(0)
        ->and(FinanceSetting::current()->exists)->toBeFalse()
        ->and(FinanceSetting::current()->eosbPolicy()->tierBoundaryMonths)->toBe(60);
});

test('the sidebar shows the settings link to finance and hides it from hr', function () {
    [$owner] = financeSettingsTenant();

    $financeManager = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $financeManager->assignRole(TenantPermissionCatalog::ROLE_FINANCE_MANAGER);

    $this->actingAs($financeManager)
        ->get(route('finance.settings.edit'))
        ->assertOk()
        ->assertSee('إعدادات نهاية الخدمة');

    /*
     * The HR Manager is the meaningful negative: they hold a broad management
     * role and land on their own dashboard, so the link's absence there proves
     * it is gated by the permission rather than by which page is rendered.
     * `tenant.dashboard` is not usable for this — it redirects every non-Owner
     * to their role's dashboard.
     */
    $hrManager = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $hrManager->assignRole(TenantPermissionCatalog::ROLE_HR_MANAGER);

    $this->actingAs($hrManager)
        ->get(route('tenant.hr.dashboard'))
        ->assertOk()
        ->assertDontSee('إعدادات نهاية الخدمة');
});

test('an existing settlement is untouched when the rules change', function () {
    [$user, $employee] = financeSettingsTenant();

    $settlement = app(GenerateSettlementAction::class)
        ->handle($employee, Carbon::parse('2026-08-31'), OffboardingReason::Termination, author: $user);

    $before = $settlement->only(['eosb_amount', 'total_amount']);

    $this->put(route('finance.settings.update'), [
        'eosb_enabled' => '1',
        'eosb_tier_boundary_months' => 12,
        'eosb_lower_tier_percent' => '300',
        'eosb_upper_tier_percent' => '300',
        'eosb_resignation_taper' => [['months' => 0, 'percent' => '100']],
        'nominal_month_days' => 22,
        'nominal_day_hours' => 8,
    ])->assertRedirect();

    expect(OffboardingSettlement::query()->findOrFail($settlement->id)->only(['eosb_amount', 'total_amount']))
        ->toBe($before);
});
