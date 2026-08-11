<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\PlatformNotification;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\URL;

uses(RefreshDatabase::class);

/**
 * Regression cover for two shipped defects in the signup → review handoff.
 *
 * 1. Verifying the Owner's email marked the USER verified but left the TENANT
 *    in `pending_verification`, so the account never entered the review queue
 *    and ApproveTenantAction — which accepts only `pending_approval` — could
 *    never act on it.
 * 2. The Super Admin notification linked to `/admin/tenants/{id}` while the
 *    controller resolves by slug, so every click 404'd.
 *
 * The pre-existing EmailVerificationTest covers the same route under
 * `Event::fake()`, which suppresses the listener the transition depends on.
 * These tests deliberately let events fire.
 *
 * @return array{0: Tenant, 1: User}
 */
function unverifiedTenant(): array
{
    $tenant = Tenant::factory()->create([
        'name' => 'شركة الواحة',
        'slug' => 'waha-'.uniqid(),
        'status' => TenantStatus::PendingVerification,
    ]);

    $owner = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

    return [$tenant, $owner];
}

function verificationUrlFor(User $user): string
{
    return URL::temporarySignedRoute('verification.verify', now()->addMinutes(60), [
        'id' => $user->id,
        'hash' => sha1($user->email),
    ]);
}

// ---------------------------------------------------------------------------
// Defect 1 — the tenant never left pending_verification
// ---------------------------------------------------------------------------

test('verifying the owner email advances the tenant into the review queue', function () {
    [$tenant, $owner] = unverifiedTenant();

    $this->actingAs($owner)
        ->get(verificationUrlFor($owner))
        ->assertRedirect(route('dashboard.setup'));

    expect($owner->refresh()->hasVerifiedEmail())->toBeTrue()
        // The bug: this stayed PendingVerification, and every downstream rule
        // keys off the tenant status rather than the user's.
        ->and($tenant->refresh()->status)->toBe(TenantStatus::PendingApproval);
});

test('the tenant becomes approvable only after verification', function () {
    actingAsPlatformOperator();
    [$tenant, $owner] = unverifiedTenant();

    // Before: the approve action refuses anything not pending_approval, so the
    // Super Admin had no route to activating this customer at all.
    $this->post(route('admin.tenants.approve', $tenant->slug))->assertRedirect();
    expect($tenant->refresh()->status)->toBe(TenantStatus::PendingVerification);

    $this->actingAs($owner)->get(verificationUrlFor($owner));

    actingAsPlatformOperator();
    $this->post(route('admin.tenants.approve', $tenant->slug))->assertRedirect();
    expect($tenant->refresh()->status)->toBe(TenantStatus::Active);
});

test('re-opening the verification link does not disturb a decided tenant', function () {
    [$tenant, $owner] = unverifiedTenant();
    $url = verificationUrlFor($owner);

    $this->actingAs($owner)->get($url);
    expect($tenant->refresh()->status)->toBe(TenantStatus::PendingApproval);

    $tenant->forceFill(['status' => TenantStatus::Suspended])->save();

    // A signed link can be opened twice. The second visit must not drag an
    // already-decided tenant back into the review queue.
    $this->actingAs($owner->fresh())->get($url);

    expect($tenant->refresh()->status)->toBe(TenantStatus::Suspended);
});

test('an employee verifying does not advance the tenant on the owner behalf', function () {
    [$tenant, $owner] = unverifiedTenant();

    $employee = User::factory()->unverified()->create(['tenant_id' => $tenant->id]);

    $this->actingAs($employee)->get(verificationUrlFor($employee));

    expect($employee->refresh()->hasVerifiedEmail())->toBeTrue()
        // Only the registering Owner's confirmation puts the account in front
        // of a Super Admin.
        ->and($tenant->refresh()->status)->toBe(TenantStatus::PendingVerification)
        ->and($owner->refresh()->hasVerifiedEmail())->toBeFalse();
});

test('a platform operator verifying their own email is unaffected', function () {
    $operator = User::factory()->unverified()->create(['tenant_id' => null]);

    // The listener must tolerate a user with no tenant rather than fatal.
    $this->actingAs($operator)->get(verificationUrlFor($operator));

    expect($operator->refresh()->hasVerifiedEmail())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Defect 2 — the notification linked to the id
// ---------------------------------------------------------------------------

test('the pending approval notification links to a URL that actually resolves', function () {
    [$tenant, $owner] = unverifiedTenant();

    $this->actingAs($owner)->get(verificationUrlFor($owner));

    $notification = PlatformNotification::query()
        ->where('category', PlatformNotification::CATEGORY_APPROVAL)
        ->latest('id')
        ->first();

    expect($notification)->not->toBeNull()
        ->and($notification->target_url)->toContain($tenant->slug)
        // The bug: this was "/admin/tenants/12", built from the primary key,
        // while the controller resolves by slug.
        ->and($notification->target_url)->not->toContain('/tenants/'.$tenant->id);

    // The real proof — follow the stored link and require a 200.
    actingAsPlatformOperator();
    $this->get($notification->target_url)->assertOk();
});

test('route generation for a tenant uses the slug the controller resolves by', function () {
    $tenant = Tenant::factory()->create(['slug' => 'route-key-'.uniqid()]);

    // Passing the model must produce the same URL as passing the slug. Before
    // getRouteKeyName() these disagreed, and only the explicit-slug call sites
    // worked.
    expect(route('admin.tenants.show', $tenant))
        ->toBe(route('admin.tenants.show', $tenant->slug))
        ->and($tenant->getRouteKey())->toBe($tenant->slug);
});

// ---------------------------------------------------------------------------
// The notification now fires at the right moment
// ---------------------------------------------------------------------------

test('no review notification is published while the tenant is still unverified', function () {
    unverifiedTenant();

    // It used to be published at registration, announcing a tenant that was
    // still pending_verification and could not be approved.
    expect(PlatformNotification::query()->where('category', PlatformNotification::CATEGORY_APPROVAL)->count())
        ->toBe(0);
});

test('the notification body reads in Arabic rather than leaking the enum name', function () {
    [, $owner] = unverifiedTenant();

    $this->actingAs($owner)->get(verificationUrlFor($owner));

    $notification = PlatformNotification::query()
        ->where('category', PlatformNotification::CATEGORY_APPROVAL)
        ->latest('id')
        ->first();

    expect($notification->body)->toContain('بانتظار الاعتماد')
        ->and($notification->body)->not->toContain('Pending Approval');
});
