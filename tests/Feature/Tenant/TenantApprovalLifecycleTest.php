<?php

use App\Domain\Tenancy\Actions\ApproveTenantAction;
use App\Domain\Tenancy\Actions\RegisterTenantAction;
use App\Domain\Tenancy\Actions\RejectTenantAction;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Exceptions\TenantReviewException;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantPlanResolver;
use App\Mail\Tenancy\TenantApprovedMail;
use App\Mail\Tenancy\TenantRejectedMail;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * @return array{0: Tenant, 1: User}
 */
function pendingRegistration(string $planSlug = 'growth'): array
{
    test()->seed(PlanSeeder::class);

    [$tenant, $owner] = app(RegisterTenantAction::class)->handle([
        'company_name' => 'شركة الأفق',
        'company_slug' => 'ofoq-'.uniqid(),
        'industry' => 'technology',
        'team_size' => '11-50',
        'plan' => $planSlug,
        'name' => 'عبدالله الشمري',
        'email' => 'owner-'.uniqid().'@example.com',
        'password' => 'Password123!',
    ]);

    // Email verification is what moves a registration into the review queue.
    $tenant->forceFill(['status' => TenantStatus::PendingApproval])->save();

    return [$tenant->refresh(), $owner];
}

// ---------------------------------------------------------------------------
// Registration
// ---------------------------------------------------------------------------

test('registration creates a pending tenant with its owner and selected plan', function () {
    $this->seed(PlanSeeder::class);

    [$tenant, $owner] = app(RegisterTenantAction::class)->handle([
        'company_name' => 'شركة الأفق',
        'company_slug' => 'ofoq',
        'industry' => 'technology',
        'team_size' => '11-50',
        'plan' => 'growth',
        'name' => 'عبدالله الشمري',
        'email' => 'owner@example.com',
        'password' => 'Password123!',
    ]);

    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();

    expect($tenant->status)->toBe(TenantStatus::PendingVerification)
        ->and($tenant->plan_id)->toBe($growth->id)
        // The FK and the denormalised slug are written together, so they
        // cannot start out disagreeing.
        ->and($tenant->plan)->toBe('growth')
        ->and($tenant->activated_at)->toBeNull()
        ->and($owner->tenant_id)->toBe($tenant->id);
});

test('an unrecognised plan slug leaves the link empty rather than guessing', function () {
    $this->seed(PlanSeeder::class);

    [$tenant] = app(RegisterTenantAction::class)->handle([
        'company_name' => 'مؤسسة',
        'company_slug' => 'unknown-plan-co',
        'industry' => null,
        'team_size' => null,
        'plan' => 'does-not-exist',
        'name' => 'مالك',
        'email' => 'x@example.com',
        'password' => 'Password123!',
    ]);

    expect($tenant->plan_id)->toBeNull();
});

// ---------------------------------------------------------------------------
// Approval
// ---------------------------------------------------------------------------

test('approval activates the tenant, stamps the reviewer and mails the owner', function () {
    Mail::fake();

    [$tenant, $owner] = pendingRegistration();
    $reviewer = User::factory()->create(['tenant_id' => null]);

    app(ApproveTenantAction::class)->handle($tenant, $reviewer);

    $tenant->refresh();

    expect($tenant->status)->toBe(TenantStatus::Active)
        ->and($tenant->activated_at)->not->toBeNull()
        ->and($tenant->reviewed_by)->toBe($reviewer->id)
        ->and($tenant->reviewed_at)->not->toBeNull()
        ->and($tenant->rejection_reason)->toBeNull();

    Mail::assertSent(
        TenantApprovedMail::class,
        fn (TenantApprovedMail $mail): bool => $mail->hasTo($owner->email)
    );
});

test('approval can override the plan chosen at registration', function () {
    Mail::fake();

    [$tenant] = pendingRegistration('startup');
    $reviewer = User::factory()->create(['tenant_id' => null]);
    $enterprise = Plan::query()->where('slug', 'enterprise')->firstOrFail();

    app(ApproveTenantAction::class)->handle($tenant, $reviewer, $enterprise->id);

    $tenant->refresh();

    // Both the FK and the cached slug move together.
    expect($tenant->plan_id)->toBe($enterprise->id)
        ->and($tenant->plan)->toBe('enterprise');
});

test('an unverified registration cannot be approved', function () {
    Mail::fake();

    [$tenant] = pendingRegistration();
    $tenant->forceFill(['status' => TenantStatus::PendingVerification])->save();

    $reviewer = User::factory()->create(['tenant_id' => null]);

    /*
     * ADR-05: approving straight out of pending_verification would hand a live
     * workspace to an unconfirmed email address — the fake-signup path the
     * verification gate exists to close.
     */
    expect(fn () => app(ApproveTenantAction::class)->handle($tenant->refresh(), $reviewer))
        ->toThrow(TenantReviewException::class);

    expect($tenant->refresh()->status)->toBe(TenantStatus::PendingVerification);
    Mail::assertNothingSent();
});

test('an already active tenant cannot be approved twice', function () {
    Mail::fake();

    [$tenant] = pendingRegistration();
    $reviewer = User::factory()->create(['tenant_id' => null]);

    app(ApproveTenantAction::class)->handle($tenant, $reviewer);

    expect(fn () => app(ApproveTenantAction::class)->handle($tenant->refresh(), $reviewer))
        ->toThrow(TenantReviewException::class, 'مفعّلة بالفعل');
});

test('approval is written to the platform audit log', function () {
    Mail::fake();

    [$tenant] = pendingRegistration();
    $reviewer = User::factory()->create(['tenant_id' => null]);

    app(ApproveTenantAction::class)->handle($tenant, $reviewer);

    $log = DB::table('platform_audit_logs')->where('action', 'tenant.approved')->first();

    expect($log)->not->toBeNull()
        ->and(json_decode((string) $log->meta, true)['tenant_id'])->toBe($tenant->id);
});

// ---------------------------------------------------------------------------
// Rejection
// ---------------------------------------------------------------------------

test('rejection records the reason, the reviewer and mails the owner', function () {
    Mail::fake();

    [$tenant, $owner] = pendingRegistration();
    $reviewer = User::factory()->create(['tenant_id' => null]);

    app(RejectTenantAction::class)->handle($tenant, $reviewer, 'بيانات السجل التجاري غير مكتملة.');

    $tenant->refresh();

    expect($tenant->status)->toBe(TenantStatus::Rejected)
        ->and($tenant->rejection_reason)->toBe('بيانات السجل التجاري غير مكتملة.')
        ->and($tenant->reviewed_by)->toBe($reviewer->id)
        // Never approved, so never activated — this keeps "activated_at is not
        // null" a reliable test for "was live at some point".
        ->and($tenant->activated_at)->toBeNull();

    Mail::assertSent(
        TenantRejectedMail::class,
        fn (TenantRejectedMail $mail): bool => $mail->hasTo($owner->email)
            && str_contains($mail->reason, 'السجل التجاري')
    );
});

test('a rejection without a reason is refused', function () {
    Mail::fake();

    [$tenant] = pendingRegistration();
    $reviewer = User::factory()->create(['tenant_id' => null]);

    expect(fn () => app(RejectTenantAction::class)->handle($tenant, $reviewer, '   '))
        ->toThrow(TenantReviewException::class, 'سبب الرفض');

    expect($tenant->refresh()->status)->toBe(TenantStatus::PendingApproval);
    Mail::assertNothingSent();
});

test('rejection preserves the tenant and its owner rather than deleting them', function () {
    Mail::fake();

    [$tenant, $owner] = pendingRegistration();
    $reviewer = User::factory()->create(['tenant_id' => null]);

    app(RejectTenantAction::class)->handle($tenant, $reviewer, 'غير مؤهل حالياً.');

    // The row is the record that the application happened, and a deleted
    // account cannot be reinstated if the refusal is later overturned.
    expect(Tenant::query()->whereKey($tenant->id)->exists())->toBeTrue()
        ->and(User::query()->whereKey($owner->id)->exists())->toBeTrue();
});

// ---------------------------------------------------------------------------
// Access isolation while pending
// ---------------------------------------------------------------------------

test('a pending tenant is blocked from operational modules with the review notice', function () {
    [$tenant, $owner] = pendingRegistration();
    $owner->forceFill(['email_verified_at' => now()])->save();

    $response = $this->actingAs($owner)->get('/app/hr/employees');

    $response->assertForbidden();

    expect($response->exception?->getMessage())
        ->toBe('حسابك قيد المراجعة - يمكنك استكمال بيانات الشركة لحين تفعيل الحساب');
});

test('a pending tenant may still reach the company setup wizard', function () {
    [$tenant, $owner] = pendingRegistration();
    $owner->forceFill(['email_verified_at' => now()])->save();

    /*
     * The isolation is structural: setup routes are registered under
     * `tenant.context` and never under `tenant.active`, so onboarding stays
     * reachable without the middleware needing a route allowlist.
     */
    $this->actingAs($owner)->get('/dashboard/setup')->assertOk();
});

test('each blocked status explains itself differently', function (TenantStatus $status, string $needle) {
    [$tenant, $owner] = pendingRegistration();
    $owner->forceFill(['email_verified_at' => now()])->save();
    $tenant->forceFill(['status' => $status])->save();

    $response = $this->actingAs($owner)->get('/app/hr/employees');

    $response->assertForbidden();
    expect($response->exception?->getMessage())->toContain($needle);
})->with([
    'rejected' => [TenantStatus::Rejected, 'تم رفض طلب تسجيل'],
    'suspended' => [TenantStatus::Suspended, 'تم إيقاف حساب مؤسستك'],
    'cancelled' => [TenantStatus::Cancelled, 'تم إلغاء اشتراك'],
]);

test('an approved tenant reaches operational modules', function () {
    Mail::fake();

    [$tenant, $owner] = pendingRegistration();
    $owner->forceFill(['email_verified_at' => now()])->save();

    app(ApproveTenantAction::class)->handle($tenant, User::factory()->create(['tenant_id' => null]));

    $this->actingAs($owner->refresh())->get('/app/hr/employees')->assertOk();
});

// ---------------------------------------------------------------------------
// Plan resolver
// ---------------------------------------------------------------------------

test('the resolver reads capacity limits from the tenant plan', function () {
    [$tenant] = pendingRegistration('startup');

    $resolver = app(TenantPlanResolver::class);

    // PlanSeeder gives startup max_employees = 25.
    expect($resolver->limitFor('max_employees', $tenant))->toBe(25)
        ->and($resolver->hasCapacityFor('max_employees', 24, $tenant))->toBeTrue()
        ->and($resolver->hasCapacityFor('max_employees', 25, $tenant))->toBeFalse()
        ->and($resolver->remainingCapacity('max_employees', 20, $tenant))->toBe(5);
});

test('unlimited reads as uncapped, not as zero', function () {
    [$tenant] = pendingRegistration('enterprise');

    $resolver = app(TenantPlanResolver::class);

    expect($resolver->limitFor('max_employees', $tenant))->toBe(TenantPlanResolver::UNLIMITED)
        ->and($resolver->hasCapacityFor('max_employees', 100_000, $tenant))->toBeTrue()
        ->and($resolver->remainingCapacity('max_employees', 100, $tenant))->toBeNull();
});

test('a tenant whose plan no longer exists is uncapped rather than locked out', function () {
    $this->seed(PlanSeeder::class);

    /*
     * The realistic shape of "no plan": the plans row was deleted, so the FK
     * was nulled by nullOnDelete and the denormalised slug is left pointing at
     * nothing. `tenants.plan` is NOT NULL, so a literal null is not reachable.
     */
    $tenant = Tenant::factory()->active()->create([
        'plan' => 'retired-tier',
        'plan_id' => null,
    ]);

    $resolver = app(TenantPlanResolver::class);

    /*
     * Plans are a commercial layer on a working product. A tenant whose plan
     * row was deleted must keep operating, not have every creation screen
     * refuse it.
     */
    expect($resolver->planFor($tenant))->toBeNull()
        ->and($resolver->limitFor('max_employees', $tenant))->toBe(TenantPlanResolver::UNLIMITED)
        ->and($resolver->hasCapacityFor('max_employees', 999, $tenant))->toBeTrue();
});

test('an absent feature flag is not granted', function () {
    [$tenant] = pendingRegistration('startup');

    // Inverse default to capacity on purpose: a missing capacity row means "we
    // never capped this"; a missing feature row means "this plan lacks it".
    expect(app(TenantPlanResolver::class)->hasFeature('ai_assistant', $tenant))->toBeFalse();
});

test('the resolver prefers the FK when the cached slug disagrees', function () {
    [$tenant] = pendingRegistration('startup');
    $enterprise = Plan::query()->where('slug', 'enterprise')->firstOrFail();

    // Simulate a stale denormalised slug left by an older write path.
    $tenant->forceFill(['plan_id' => $enterprise->id, 'plan' => 'startup'])->save();

    expect(app(TenantPlanResolver::class)->planFor($tenant->refresh())?->slug)->toBe('enterprise');
});
