<?php

use App\Domain\Tenancy\Enums\BillingCycle;
use App\Domain\Tenancy\Enums\SubscriptionStatus;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\TenantInvoice;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\Plan;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('tenant owner can view subscription portal with plan details and usage', function () {
    $this->seed(PlanSeeder::class);

    $user = actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        [
            'status' => 'active',
            'plan' => 'growth',
            'billing_cycle' => BillingCycle::Monthly,
            'subscription_status' => SubscriptionStatus::Active,
            'renews_at' => now()->addDays(20),
        ],
    );

    Employee::factory()->count(3)->create(['tenant_id' => $user->tenant_id]);
    Department::factory()->count(2)->create(['tenant_id' => $user->tenant_id]);

    TenantInvoice::factory()->create([
        'tenant_id' => $user->tenant_id,
        'number' => 'INV-1001',
        'amount' => 129,
        'status' => 'paid',
    ]);

    $plan = Plan::query()->where('slug', 'growth')->first();

    $this->get(route('tenant.subscription.index'))
        ->assertOk()
        ->assertSee('إدارة الاشتراك والخطط', false)
        ->assertSee($plan->name, false)
        ->assertSee('نشط', false)
        ->assertSee('شهري', false)
        ->assertSee('الموظفون', false)
        ->assertSee('الأقسام', false)
        ->assertSee('التخزين', false)
        ->assertSee('INV-1001', false)
        ->assertSee('ترقية الخطة', false)
        ->assertSee('تجديد الاشتراك', false)
        ->assertSee('100', false);
});

test('subscription portal shows renewal warning when fewer than seven days remain', function () {
    $this->seed(PlanSeeder::class);

    actingAsTenantUser(
        TenantPermissionCatalog::ROLE_OWNER,
        [
            'status' => 'active',
            'plan' => 'startup',
            'subscription_status' => SubscriptionStatus::Active,
            'renews_at' => now()->addDays(3),
        ],
    );

    $this->get(route('tenant.subscription.index'))
        ->assertOk()
        ->assertSee('يتجدد خلال', false);
});

test('employee role cannot access subscription portal', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.subscription.index'))->assertForbidden();
});
