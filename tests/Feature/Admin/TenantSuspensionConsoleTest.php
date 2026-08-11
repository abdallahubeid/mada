<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Mail\Tenancy\TenantReactivatedMail;
use App\Mail\Tenancy\TenantSuspendedMail;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * Suspension and reactivation over HTTP, closing the Phase 1 exit criterion
 * "a Super Admin can approve/reject/suspend tenants".
 *
 * The approve/reject half is covered by TenantReviewConsoleTest; this file
 * covers the two reversible transitions, the listing filters that were
 * previously decorative markup, and the isolation invariant that gives
 * suspension its meaning.
 */
$reason = 'تكرار مخالفة شروط الاستخدام رغم التنبيه المرسل سابقاً.';

/**
 * @return array{0: Tenant, 1: User}
 */
function liveTenant(string $status = 'active'): array
{
    test()->seed(PlanSeeder::class);

    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();

    $tenant = Tenant::factory()->create([
        'name' => 'شركة النخبة',
        'slug' => 'nokhba-'.uniqid(),
        'status' => $status,
        'plan' => 'growth',
        'plan_id' => $growth->id,
        'activated_at' => now()->subMonths(6),
    ]);

    $owner = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    return [$tenant, $owner];
}

/**
 * A tenant user who can actually reach the dashboard when their tenant is
 * active — i.e. one holding `tenant.dashboard.view` through the Owner role.
 *
 * A bare User::factory() user is refused by the permission middleware whether
 * or not the tenant is suspended, so asserting 403 on one proves nothing about
 * suspension. This is what makes the access tests below meaningful.
 */
function roledMemberOf(Tenant $tenant): User
{
    app(SeedDefaultTenantRoles::class)->handle($tenant);

    $member = User::factory()->create(['tenant_id' => $tenant->id]);

    app(TenantContext::class)->setTenant($tenant);
    $member->assignRole(TenantPermissionCatalog::ROLE_OWNER);

    return $member;
}

// ---------------------------------------------------------------------------
// Suspend
// ---------------------------------------------------------------------------

test('a super admin can suspend an active tenant and the owner is told why', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant, $owner] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason])
        ->assertRedirect(route('admin.tenants.show', $tenant->slug));

    $tenant->refresh();

    expect($tenant->status)->toBe(TenantStatus::Suspended)
        ->and($tenant->suspension_reason)->toBe($reason)
        ->and($tenant->suspended_at)->not->toBeNull()
        ->and($tenant->suspended_by)->not->toBeNull();

    Mail::assertSent(TenantSuspendedMail::class, fn ($mail): bool => $mail->hasTo($owner->email));
});

test('suspension does not disturb the original activation date', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = liveTenant();

    $activatedAt = $tenant->activated_at;

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);

    // `activated_at` answers "was this account ever live", which stays true
    // through a suspension. Overwriting it would make a suspended tenant
    // indistinguishable from one that was never approved.
    expect($tenant->refresh()->activated_at->timestamp)->toBe($activatedAt->timestamp);
});

test('suspension requires a reason of real substance', function () {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => ''])
        ->assertSessionHasErrors('suspension_reason');

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => 'لا'])
        ->assertSessionHasErrors('suspension_reason');

    expect($tenant->refresh()->status)->toBe(TenantStatus::Active);
    Mail::assertNothingSent();
});

test('only a live tenant can be suspended', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();

    // A pending registration still needs its review; a rejected one already
    // had it. Neither is a thing you suspend.
    foreach (['pending_approval', 'rejected', 'cancelled'] as $status) {
        [$tenant] = liveTenant($status);

        $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason])
            ->assertRedirect();

        expect($tenant->refresh()->status->value)->toBe($status);
    }

    Mail::assertNothingSent();
});

test('suspending an already suspended tenant fails gracefully instead of throwing', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);

    // Models a stale open tab: the second submit must surface a flash error,
    // not a 500, and must not re-notify the owner.
    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => 'سبب مختلف تماماً هنا.'])
        ->assertRedirect();

    expect($tenant->refresh()->suspension_reason)->toBe($reason);
    Mail::assertSentCount(1);
});

test('a suspension is written to the platform audit log', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);

    $entry = PlatformAuditLog::query()->where('action', 'tenant.suspended')->latest('id')->first();

    expect($entry)->not->toBeNull()
        ->and($entry->meta['tenant_id'])->toBe($tenant->id)
        ->and($entry->meta['reason'])->toBe($reason);
});

// ---------------------------------------------------------------------------
// The invariant suspension exists to enforce
// ---------------------------------------------------------------------------

test('a suspended tenant loses access to operational routes', function () use ($reason) {
    Mail::fake();
    [$tenant] = liveTenant();
    $member = roledMemberOf($tenant);

    /*
     * Suspension is only meaningful if EnsureTenantActive actually refuses the
     * tenant's own users afterwards. The "before" assertion is the load-bearing
     * half: without it a 403 after suspension could just as easily mean the
     * member never had access at all, and the test would pass against a
     * suspend action that did nothing.
     */
    $this->actingAs($member->fresh())->get(route('dashboard'))->assertOk();

    actingAsPlatformOperator();
    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);

    /*
     * `fresh()` per request, not the same instance twice: actingAs pins that
     * exact object as the user resolver, so its `tenant` relation stays cached
     * from the previous call and the middleware would read a pre-suspension
     * status. A real request rebuilds the user from the session, so this is a
     * test artifact rather than a production one.
     */
    $this->actingAs($member->fresh())->get(route('dashboard'))->assertForbidden();
});

// ---------------------------------------------------------------------------
// Reactivate
// ---------------------------------------------------------------------------

test('a super admin can reactivate a suspended tenant', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant, $owner] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);
    $this->post(route('admin.tenants.reactivate', $tenant->slug))
        ->assertRedirect(route('admin.tenants.show', $tenant->slug));

    $tenant->refresh();

    expect($tenant->status)->toBe(TenantStatus::Active)
        // Cleared, not kept: a live tenant still advertising why it was once
        // suspended would render a stale reason beside a green badge.
        ->and($tenant->suspension_reason)->toBeNull()
        ->and($tenant->suspended_at)->toBeNull()
        ->and($tenant->suspended_by)->toBeNull();

    Mail::assertSent(TenantReactivatedMail::class, fn ($mail): bool => $mail->hasTo($owner->email));
});

test('reactivation restores access for the tenant users', function () use ($reason) {
    Mail::fake();
    [$tenant] = liveTenant();
    $member = roledMemberOf($tenant);

    actingAsPlatformOperator();
    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);
    $this->actingAs($member->fresh())->get(route('dashboard'))->assertForbidden();

    actingAsPlatformOperator();
    $this->post(route('admin.tenants.reactivate', $tenant->slug));

    $this->actingAs($member->fresh())->get(route('dashboard'))->assertOk();
});

test('reactivation is refused for anything that is not suspended', function () {
    Mail::fake();
    actingAsPlatformOperator();

    // Not a general "make active" switch: routing cancelled or rejected
    // through here would bypass the decisions those states record.
    foreach (['active', 'cancelled', 'rejected', 'pending_approval'] as $status) {
        [$tenant] = liveTenant($status);

        $this->post(route('admin.tenants.reactivate', $tenant->slug))->assertRedirect();

        expect($tenant->refresh()->status->value)->toBe($status);
    }

    Mail::assertNothingSent();
});

test('the reactivation audit entry preserves the reason it lifted', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);
    $this->post(route('admin.tenants.reactivate', $tenant->slug));

    $entry = PlatformAuditLog::query()->where('action', 'tenant.reactivated')->latest('id')->first();

    // The tenant row no longer holds the reason, so the audit log is the only
    // remaining record of what the suspension was for.
    expect($entry)->not->toBeNull()
        ->and($entry->meta['lifted_reason'])->toBe($reason);
});

// ---------------------------------------------------------------------------
// The detail screen
// ---------------------------------------------------------------------------

test('the detail page explains why a tenant is suspended and stops once lifted', function () use ($reason) {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason]);

    $this->get(route('admin.tenants.show', $tenant->slug))
        ->assertOk()
        ->assertSee($reason, false)
        ->assertSee('الحساب موقوف', false);

    $this->post(route('admin.tenants.reactivate', $tenant->slug));

    $this->get(route('admin.tenants.show', $tenant->slug))
        ->assertOk()
        ->assertDontSee($reason, false);
});

// ---------------------------------------------------------------------------
// Authorization — tenants.manage, not tenants.update
// ---------------------------------------------------------------------------

test('a role without tenants.manage can read the console but not act on it', function () use ($reason) {
    Mail::fake();
    // Support Agent holds tenants.view_any and tenants.view, and deliberately
    // no lifecycle permission: reading a customer's record is not the same
    // power as locking every one of their users out.
    actingAsPlatformOperator(PlatformPermissionCatalog::ROLE_SUPPORT_AGENT);
    [$tenant] = liveTenant();

    $this->get(route('admin.tenants'))->assertOk();
    $this->get(route('admin.tenants.show', $tenant->slug))->assertOk();

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason])
        ->assertForbidden();
    $this->post(route('admin.tenants.reactivate', $tenant->slug))->assertForbidden();

    expect($tenant->refresh()->status)->toBe(TenantStatus::Active);
    Mail::assertNothingSent();
});

test('a tenant user cannot reach the suspension endpoints', function () use ($reason) {
    Mail::fake();
    [$tenant] = liveTenant();

    $outsider = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($outsider);

    $this->post(route('admin.tenants.suspend', $tenant->slug), ['suspension_reason' => $reason])
        ->assertForbidden();
    $this->post(route('admin.tenants.reactivate', $tenant->slug))->assertForbidden();

    expect($tenant->refresh()->status)->toBe(TenantStatus::Active);
    Mail::assertNothingSent();
});

test('a guest is redirected away from the suspension endpoints', function () {
    [$tenant] = liveTenant();

    $this->post(route('admin.tenants.suspend', $tenant->slug))->assertRedirect();
    $this->post(route('admin.tenants.reactivate', $tenant->slug))->assertRedirect();
});

test('an unknown slug is a 404 on both transitions', function () use ($reason) {
    actingAsPlatformOperator();

    $this->post(route('admin.tenants.suspend', 'no-such-tenant'), ['suspension_reason' => $reason])
        ->assertNotFound();
    $this->post(route('admin.tenants.reactivate', 'no-such-tenant'))->assertNotFound();
});
