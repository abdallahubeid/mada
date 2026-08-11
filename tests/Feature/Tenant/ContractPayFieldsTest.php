<?php

use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeContract(int $tenantId, array $attributes = []): EmployeeContract
{
    $employee = Employee::factory()->create(['tenant_id' => $tenantId]);

    return EmployeeContract::factory()->create(array_merge([
        'tenant_id' => $tenantId,
        'employee_id' => $employee->id,
    ], $attributes));
}

test('a new contract defaults to salaried with an unset rate', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $contract = makeContract($user->tenant_id)->fresh();

    expect($contract->pay_basis)->toBe(PayBasis::Salaried)
        ->and($contract->base_rate)->toBe(0)
        ->and($contract->billing_rate)->toBeNull();
});

test('pay basis and contract type are independent axes', function () {
    // ADR-19: the whole point of the split. A freelancer may be hourly;
    // a full-time employee may be unpaid. Neither is derived from the other.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $freelanceHourly = makeContract($user->tenant_id, [
        'contract_type' => ContractType::Freelance,
        'pay_basis' => PayBasis::Hourly,
        'base_rate' => 12_500,
    ])->fresh();

    $fullTimeUnpaid = makeContract($user->tenant_id, [
        'contract_type' => ContractType::FullTime,
        'pay_basis' => PayBasis::Unpaid,
        'base_rate' => 0,
    ])->fresh();

    expect($freelanceHourly->contract_type)->toBe(ContractType::Freelance)
        ->and($freelanceHourly->pay_basis)->toBe(PayBasis::Hourly)
        ->and($fullTimeUnpaid->contract_type)->toBe(ContractType::FullTime)
        ->and($fullTimeUnpaid->pay_basis)->toBe(PayBasis::Unpaid);
});

test('rates round trip as exact integer minor units', function () {
    // ADR-20: 8,432.17 SAR is 843217 halalas — no float anywhere in the path.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $contract = makeContract($user->tenant_id, [
        'pay_basis' => PayBasis::Salaried,
        'base_rate' => 843_217,
        'billing_rate' => 1_250_000,
        'pay_currency' => 'SAR',
    ])->fresh();

    expect($contract->base_rate)->toBe(843_217)->toBeInt()
        ->and($contract->billing_rate)->toBe(1_250_000)->toBeInt()
        ->and($contract->pay_currency)->toBe('SAR');

    // And the column itself holds an integer, not a decimal string.
    $raw = DB::table('employee_contracts')->where('id', $contract->id)->value('base_rate');
    expect((int) $raw)->toBe(843_217);
});

test('an unset pay rate is detected only for payable contracts', function () {
    // BR-301a: the backfill left every existing contract salaried/0, which is
    // safe but silent. Payroll run generation refuses to open while this is true.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $tenantId = $user->tenant_id;

    $unpricedSalaried = makeContract($tenantId, ['pay_basis' => PayBasis::Salaried, 'base_rate' => 0]);
    $unpricedHourly = makeContract($tenantId, ['pay_basis' => PayBasis::Hourly, 'base_rate' => 0]);
    $pricedSalaried = makeContract($tenantId, ['pay_basis' => PayBasis::Salaried, 'base_rate' => 500_000]);
    $unpaid = makeContract($tenantId, ['pay_basis' => PayBasis::Unpaid, 'base_rate' => 0]);

    expect($unpricedSalaried->hasUnsetPayRate())->toBeTrue()
        ->and($unpricedHourly->hasUnsetPayRate())->toBeTrue()
        ->and($pricedSalaried->hasUnsetPayRate())->toBeFalse()
        ->and($unpaid->hasUnsetPayRate())->toBeFalse();
});

test('pay basis reports whether it needs a rate and whether it consumes worked minutes', function () {
    expect(PayBasis::Salaried->requiresBaseRate())->toBeTrue()
        ->and(PayBasis::Hourly->requiresBaseRate())->toBeTrue()
        ->and(PayBasis::Unpaid->requiresBaseRate())->toBeFalse();

    // Only hourly multiplies work_ledger_entries.worked_minutes.
    expect(PayBasis::Hourly->usesWorkedMinutes())->toBeTrue()
        ->and(PayBasis::Salaried->usesWorkedMinutes())->toBeFalse()
        ->and(PayBasis::Unpaid->usesWorkedMinutes())->toBeFalse();
});

test('every pay basis carries an arabic label and the enum exposes its values', function () {
    foreach (PayBasis::cases() as $basis) {
        expect($basis->label())->not->toBe('');
    }

    expect(PayBasis::values())->toBe(['salaried', 'hourly', 'unpaid']);
});

test('pay currency is frozen on the contract rather than read live', function () {
    // BR-301b: changing the tenant currency later must not reinterpret history.
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $contract = makeContract($user->tenant_id, ['pay_currency' => 'AED']);

    DB::table('org_settings')->where('tenant_id', $user->tenant_id)->update(['currency' => 'KWD']);

    expect($contract->fresh()->pay_currency)->toBe('AED');
});

test('the contract type enum still carries no pay semantics', function () {
    // Guards against a future regression that re-collapses the two axes.
    expect(ContractType::values())->toBe(['full_time', 'part_time', 'fixed_term', 'freelance'])
        ->and(ContractType::values())->not->toContain('salaried')
        ->and(ContractType::values())->not->toContain('hourly');
});
