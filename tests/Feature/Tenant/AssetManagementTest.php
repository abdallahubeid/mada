<?php

use App\Domain\Tenancy\Enums\AssetCategory;
use App\Domain\Tenancy\Enums\AssetCondition;
use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\AssetAssignment;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner can create an asset and auto-generate asset code', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('tenant.assets.index'))
        ->assertOk()
        ->assertSee('إدارة العُهد والأصول')
        ->assertSee('data-testid="kpi-assets-total"', false);

    $this->post(route('tenant.assets.store'), [
        'name' => 'MacBook Pro 14',
        'category' => AssetCategory::Laptop->value,
        'serial_number' => 'SN-ABC123',
        'purchase_date' => '2026-01-15',
        'purchase_cost' => '8999.50',
        'status' => AssetStatus::Available->value,
        'notes' => 'جهاز تطوير',
    ])->assertRedirect(route('tenant.assets.index'));

    $asset = Asset::query()->first();

    expect($asset)->not->toBeNull()
        ->and($asset->tenant_id)->toBe($user->tenant_id)
        ->and($asset->name)->toBe('MacBook Pro 14')
        ->and($asset->asset_code)->toBe('AST-001')
        ->and($asset->status)->toBe(AssetStatus::Available)
        ->and($asset->category)->toBe(AssetCategory::Laptop);
});

test('assigning and returning an asset toggles status correctly', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Sara',
        'last_name' => 'Ali',
    ]);

    $asset = Asset::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'iPhone 15',
        'asset_code' => 'AST-010',
        'category' => AssetCategory::Phone,
        'status' => AssetStatus::Available,
    ]);

    $this->post(route('tenant.assets.assign', $asset), [
        'employee_id' => $employee->id,
        'condition_on_assign' => AssetCondition::New->value,
        'notes' => 'تسليم عند الانضمام',
    ])->assertRedirect(route('tenant.assets.index'));

    $asset->refresh();
    $assignment = AssetAssignment::query()->where('asset_id', $asset->id)->first();

    expect($asset->status)->toBe(AssetStatus::Assigned)
        ->and($assignment)->not->toBeNull()
        ->and($assignment->employee_id)->toBe($employee->id)
        ->and($assignment->returned_at)->toBeNull()
        ->and($assignment->assigned_by)->toBe($user->id)
        ->and($assignment->condition_on_assign)->toBe(AssetCondition::New);

    $this->post(route('tenant.assets.return', $asset), [
        'condition_on_return' => AssetCondition::Good->value,
        'status' => AssetStatus::Available->value,
        'notes' => 'أعيد بحالة جيدة',
    ])->assertRedirect(route('tenant.assets.index'));

    $asset->refresh();
    $assignment->refresh();

    expect($asset->status)->toBe(AssetStatus::Available)
        ->and($assignment->returned_at)->not->toBeNull()
        ->and($assignment->condition_on_return)->toBe(AssetCondition::Good);
});

test('employee custody page and profile tab list active assigned assets', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
        'first_name' => 'Omar',
        'last_name' => 'Hassan',
    ]);

    $activeAsset = Asset::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'ختم الشركة',
        'asset_code' => 'AST-020',
        'category' => AssetCategory::DocumentSeal,
        'status' => AssetStatus::Assigned,
    ]);

    $returnedAsset = Asset::factory()->create([
        'tenant_id' => $user->tenant_id,
        'name' => 'شاحن قديم',
        'asset_code' => 'AST-021',
        'category' => AssetCategory::Accessory,
        'status' => AssetStatus::Available,
    ]);

    AssetAssignment::factory()->create([
        'tenant_id' => $user->tenant_id,
        'asset_id' => $activeAsset->id,
        'employee_id' => $employee->id,
        'assigned_by' => $user->id,
        'returned_at' => null,
        'condition_on_assign' => AssetCondition::Good,
    ]);

    AssetAssignment::factory()->create([
        'tenant_id' => $user->tenant_id,
        'asset_id' => $returnedAsset->id,
        'employee_id' => $employee->id,
        'assigned_by' => $user->id,
        'assigned_at' => now()->subMonths(2),
        'returned_at' => now()->subMonth(),
        'condition_on_assign' => AssetCondition::Fair,
        'condition_on_return' => AssetCondition::Fair,
    ]);

    $this->get(route('tenant.assets.employee', $employee))
        ->assertOk()
        ->assertSee('عهدة Omar Hassan')
        ->assertSee('AST-020')
        ->assertSee('ختم الشركة')
        ->assertSee('AST-021');

    $this->get(route('hr.employees.show', [$employee, 'tab' => 'assets']))
        ->assertOk()
        ->assertSee('العُهد والأصول')
        ->assertSee('AST-020')
        ->assertSee('ختم الشركة');
});

test('employee role cannot manage or view company assets', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.assets.index'))->assertForbidden();

    $this->post(route('tenant.assets.store'), [
        'name' => 'Unauthorized Laptop',
        'category' => AssetCategory::Laptop->value,
    ])->assertForbidden();
});

test('cannot assign an asset that is not available', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $user->tenant_id,
    ]);

    $asset = Asset::factory()->create([
        'tenant_id' => $user->tenant_id,
        'status' => AssetStatus::UnderMaintenance,
    ]);

    $this->from(route('tenant.assets.index'))
        ->post(route('tenant.assets.assign', $asset), [
            'employee_id' => $employee->id,
            'condition_on_assign' => AssetCondition::Good->value,
        ])
        ->assertRedirect(route('tenant.assets.index'));

    expect(AssetAssignment::query()->where('asset_id', $asset->id)->exists())->toBeFalse()
        ->and($asset->fresh()->status)->toBe(AssetStatus::UnderMaintenance);
});
