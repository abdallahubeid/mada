<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Mail\Tenancy\TenantApprovedMail;
use App\Mail\Tenancy\TenantRejectedMail;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;

uses(RefreshDatabase::class);

/**
 * @return array{0: Tenant, 1: User}
 */
function reviewableTenant(): array
{
    test()->seed(PlanSeeder::class);

    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();

    $tenant = Tenant::factory()->create([
        'name' => 'شركة الأفق',
        'slug' => 'ofoq-'.uniqid(),
        'status' => TenantStatus::PendingApproval,
        'plan' => 'growth',
        'plan_id' => $growth->id,
    ]);

    $owner = User::factory()->create([
        'tenant_id' => $tenant->id,
        'email_verified_at' => now(),
    ]);

    return [$tenant, $owner];
}

// ---------------------------------------------------------------------------
// The listing now renders real records
// ---------------------------------------------------------------------------

test('the console lists real pending tenants rather than fixtures', function () {
    actingAsPlatformOperator();
    [$tenant, $owner] = reviewableTenant();

    $response = $this->get(route('admin.tenants'))->assertOk();

    $response->assertSee($tenant->name, false)
        ->assertSee($tenant->slug, false)
        ->assertSee($owner->email, false);

    /*
     * The screen used to hardcode ten fictional companies. If any of those
     * strings come back, the controller has regressed to the mock.
     */
    $response->assertDontSee('متجر السلام', false)
        ->assertDontSee('مجموعة رواد', false);
});

test('the pending approval tab counts real rows', function () {
    actingAsPlatformOperator();
    reviewableTenant();
    reviewableTenant();
    Tenant::factory()->active()->create();

    $response = $this->get(route('admin.tenants', ['status' => TenantStatus::PendingApproval->value]))->assertOk();

    expect($response->viewData('counts')[TenantStatus::PendingApproval->value])->toBe(2)
        ->and($response->viewData('tenants'))->toHaveCount(2);
});

test('the detail page renders a real tenant', function () {
    actingAsPlatformOperator();
    [$tenant, $owner] = reviewableTenant();

    $this->get(route('admin.tenants.show', $tenant->slug))
        ->assertOk()
        ->assertSee($tenant->name, false)
        ->assertSee($owner->email, false);
});

test('an unknown slug is a 404 rather than a silent fallback to a fixture', function () {
    actingAsPlatformOperator();

    // The old controller fell back to "the first active mock" for any unknown
    // slug, so a typo silently showed someone else's record.
    $this->get(route('admin.tenants.show', 'no-such-tenant'))->assertNotFound();
});

// ---------------------------------------------------------------------------
// Approve / reject over HTTP
// ---------------------------------------------------------------------------

test('a super admin can approve a pending tenant from the console', function () {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant, $owner] = reviewableTenant();

    $this->post(route('admin.tenants.approve', $tenant->slug))
        ->assertRedirect(route('admin.tenants.show', $tenant->slug));

    expect($tenant->refresh()->status)->toBe(TenantStatus::Active)
        ->and($tenant->activated_at)->not->toBeNull();

    Mail::assertSent(TenantApprovedMail::class, fn ($mail): bool => $mail->hasTo($owner->email));
});

test('approving can switch the plan from the modal', function () {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = reviewableTenant();

    $startup = Plan::query()->where('slug', 'startup')->firstOrFail();

    $this->post(route('admin.tenants.approve', $tenant->slug), ['plan_id' => $startup->id])
        ->assertRedirect();

    expect($tenant->refresh()->plan_id)->toBe($startup->id)
        ->and($tenant->plan)->toBe('startup');
});

test('rejecting requires a reason of real substance', function () {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = reviewableTenant();

    $this->post(route('admin.tenants.reject', $tenant->slug), ['rejection_reason' => ''])
        ->assertSessionHasErrors('rejection_reason');

    // The applicant reads this verbatim; "لا" alone would just generate a
    // support ticket.
    $this->post(route('admin.tenants.reject', $tenant->slug), ['rejection_reason' => 'لا'])
        ->assertSessionHasErrors('rejection_reason');

    expect($tenant->refresh()->status)->toBe(TenantStatus::PendingApproval);
    Mail::assertNothingSent();
});

test('a super admin can reject with a reason and the applicant is told', function () {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant, $owner] = reviewableTenant();

    $this->post(route('admin.tenants.reject', $tenant->slug), [
        'rejection_reason' => 'بيانات السجل التجاري غير مكتملة، يرجى إعادة التقديم بعد استكمالها.',
    ])->assertRedirect();

    expect($tenant->refresh()->status)->toBe(TenantStatus::Rejected)
        ->and($tenant->rejection_reason)->toContain('السجل التجاري');

    Mail::assertSent(TenantRejectedMail::class, fn ($mail): bool => $mail->hasTo($owner->email));
});

test('approving an already active tenant fails gracefully instead of throwing', function () {
    Mail::fake();
    actingAsPlatformOperator();
    [$tenant] = reviewableTenant();

    $this->post(route('admin.tenants.approve', $tenant->slug))->assertRedirect();

    // Models a stale open tab: the second submit must surface a flash error,
    // not a 500.
    $this->post(route('admin.tenants.approve', $tenant->slug))->assertRedirect();

    expect($tenant->refresh()->status)->toBe(TenantStatus::Active);
    Mail::assertSentCount(1);
});

// ---------------------------------------------------------------------------
// Authorization
// ---------------------------------------------------------------------------

test('a tenant user cannot reach the review endpoints', function () {
    Mail::fake();
    [$tenant] = reviewableTenant();

    $outsider = User::factory()->create(['tenant_id' => $tenant->id]);
    $this->actingAs($outsider);

    $this->get(route('admin.tenants'))->assertForbidden();
    $this->post(route('admin.tenants.approve', $tenant->slug))->assertForbidden();
    $this->post(route('admin.tenants.reject', $tenant->slug), ['rejection_reason' => 'سبب كافٍ للرفض هنا.'])
        ->assertForbidden();

    expect($tenant->refresh()->status)->toBe(TenantStatus::PendingApproval);
    Mail::assertNothingSent();
});

test('a guest is redirected away from the review endpoints', function () {
    [$tenant] = reviewableTenant();

    $this->get(route('admin.tenants'))->assertRedirect();
    $this->post(route('admin.tenants.approve', $tenant->slug))->assertRedirect();
});
