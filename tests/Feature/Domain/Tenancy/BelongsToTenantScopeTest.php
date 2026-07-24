<?php

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Schema;

uses(RefreshDatabase::class);

/**
 * A disposable, tenant-scoped model used only to exercise the
 * {@see BelongsToTenant} trait and {@see TenantScope}
 * in isolation, without depending on a real domain model (Employee, Project, etc.)
 * that doesn't exist yet in Phase 1.
 */
class TestTenantWidget extends Model
{
    use BelongsToTenant;

    protected $table = 'test_tenant_widgets';

    protected $fillable = ['name'];
}

beforeEach(function () {
    Schema::create('test_tenant_widgets', function (Blueprint $table) {
        $table->id();
        $table->foreignId('tenant_id')->constrained();
        $table->string('name');
        $table->timestamps();
    });
});

test('creating a tenant-scoped model stamps tenant_id from the resolved context', function () {
    $tenant = Tenant::factory()->active()->create();
    app(TenantContext::class)->setTenant($tenant);

    $widget = TestTenantWidget::create(['name' => 'Widget A']);

    expect($widget->tenant_id)->toBe($tenant->id);
});

test('querying a tenant-scoped model only returns rows for the resolved tenant', function () {
    $tenantA = Tenant::factory()->active()->create();
    $tenantB = Tenant::factory()->active()->create();

    $context = app(TenantContext::class);

    $context->setTenant($tenantA);
    TestTenantWidget::create(['name' => 'Belongs to A']);

    $context->setTenant($tenantB);
    TestTenantWidget::create(['name' => 'Belongs to B']);

    $context->setTenant($tenantA);
    $visibleToA = TestTenantWidget::all();

    expect($visibleToA)->toHaveCount(1)
        ->and($visibleToA->first()->name)->toBe('Belongs to A');

    $context->setTenant($tenantB);
    $visibleToB = TestTenantWidget::all();

    expect($visibleToB)->toHaveCount(1)
        ->and($visibleToB->first()->name)->toBe('Belongs to B');
});

test('a tenant can never fetch another tenant record by id', function () {
    $tenantA = Tenant::factory()->active()->create();
    $tenantB = Tenant::factory()->active()->create();

    $context = app(TenantContext::class);

    $context->setTenant($tenantA);
    $widgetA = TestTenantWidget::create(['name' => 'Belongs to A']);

    $context->setTenant($tenantB);

    expect(TestTenantWidget::find($widgetA->id))->toBeNull();
});

test('with no tenant resolved the scope adds no constraint', function () {
    $tenantA = Tenant::factory()->active()->create();
    $tenantB = Tenant::factory()->active()->create();

    $context = app(TenantContext::class);

    $context->setTenant($tenantA);
    TestTenantWidget::create(['name' => 'Belongs to A']);

    $context->setTenant($tenantB);
    TestTenantWidget::create(['name' => 'Belongs to B']);

    $context->setTenant(null);

    expect(TestTenantWidget::count())->toBe(2);
});
