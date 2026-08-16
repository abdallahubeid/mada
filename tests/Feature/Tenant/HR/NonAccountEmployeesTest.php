<?php

use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Livewire\HR\NonAccountEmployees;
use App\Mail\Tenancy\EmployeeWelcomeMail;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Mail;
use Livewire\Livewire;

uses(RefreshDatabase::class);

test('the screen lists only employees without a user account', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'user_id' => null,
        'first_name' => 'سالم',
        'last_name' => 'الحربي',
    ]);

    $linked = User::factory()->create(['tenant_id' => $hr->tenant_id]);
    Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'user_id' => $linked->id,
        'first_name' => 'نورة',
        'last_name' => 'القحطاني',
    ]);

    Livewire::test(NonAccountEmployees::class)
        ->assertSee('سالم')
        ->assertDontSee('نورة');
});

test('bulk selection is gone', function () {
    /*
     * The refactor removed checkboxes in favour of per-row actions. Pinning
     * their absence stops the old bulk flow being reintroduced alongside the
     * new one, which would leave two ways to create accounts with different
     * validation.
     */
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    Employee::factory()->create(['tenant_id' => $hr->tenant_id, 'user_id' => null]);

    $component = Livewire::test(NonAccountEmployees::class);

    expect(property_exists($component->instance(), 'selected'))->toBeFalse()
        ->and(property_exists($component->instance(), 'selectPage'))->toBeFalse()
        ->and(method_exists($component->instance(), 'createAccounts'))->toBeFalse();

    $component->assertDontSee('تحديد كل الصفوف');
});

test('the details drawer shows the employee profile', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'user_id' => null,
        'first_name' => 'عبدالله',
        'last_name' => 'عبيد',
        'job_title' => 'فني صيانة',
        'phone' => '+966500000000',
    ]);

    Livewire::test(NonAccountEmployees::class)
        ->call('viewDetails', $employee->id)
        ->assertSet('viewingId', $employee->id)
        ->assertSee('فني صيانة')
        ->assertSee('+966500000000');
});

test('the create modal pre-fills an existing email and can be overridden', function () {
    Mail::fake();

    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'user_id' => null,
        'email' => 'old@example.test',
    ]);

    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $employee->id)
        ->assertSet('accountEmail', 'old@example.test')
        // The operator types a different address than the one on file.
        ->set('accountEmail', 'corrected@example.test')
        ->call('createAccount')
        ->assertHasNoErrors();

    $employee->refresh();

    expect($employee->user_id)->not->toBeNull()
        // The typed address is persisted to the employee too, not just the user.
        ->and($employee->email)->toBe('corrected@example.test')
        ->and(User::find($employee->user_id)->email)->toBe('corrected@example.test');

    Mail::assertSent(EmployeeWelcomeMail::class);
});

test('an employee with no email on file can still be given an account', function () {
    Mail::fake();

    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'user_id' => null,
        'email' => null,
    ]);

    /*
     * This is the case the removed bulk action could never handle: no address
     * on file, so the operator supplies one.
     */
    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $employee->id)
        ->assertSet('accountEmail', '')
        ->set('accountEmail', 'typed@example.test')
        ->call('createAccount')
        ->assertHasNoErrors();

    expect($employee->fresh()->email)->toBe('typed@example.test');
});

test('a duplicate email is refused inline and creates nothing', function () {
    Mail::fake();

    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    User::factory()->create(['tenant_id' => $hr->tenant_id, 'email' => 'taken@example.test']);

    $employee = Employee::factory()->create(['tenant_id' => $hr->tenant_id, 'user_id' => null]);

    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $employee->id)
        ->set('accountEmail', 'taken@example.test')
        ->call('createAccount')
        ->assertHasErrors(['accountEmail']);

    expect($employee->fresh()->user_id)->toBeNull()
        ->and(User::query()->where('email', 'taken@example.test')->count())->toBe(1);

    Mail::assertNothingSent();
});

test('an invalid email is refused', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create(['tenant_id' => $hr->tenant_id, 'user_id' => null]);

    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $employee->id)
        ->set('accountEmail', 'not-an-email')
        ->call('createAccount')
        ->assertHasErrors(['accountEmail']);

    expect($employee->fresh()->user_id)->toBeNull();
});

test('the chosen role is the one granted', function () {
    Mail::fake();

    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create(['tenant_id' => $hr->tenant_id, 'user_id' => null]);

    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $employee->id)
        ->set('accountEmail', 'manager@example.test')
        ->set('accountRole', TenantPermissionCatalog::ROLE_HR_MANAGER)
        ->call('createAccount')
        ->assertHasNoErrors();

    $user = User::query()->where('email', 'manager@example.test')->first();

    expect($user->hasRole(TenantPermissionCatalog::ROLE_HR_MANAGER))->toBeTrue();
});

test('an employee from another tenant is refused without crashing the page', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $otherTenant = Tenant::factory()->create();
    $foreign = Employee::factory()->create(['tenant_id' => $otherTenant->id, 'user_id' => null]);

    /*
     * `creatingId` is client-supplied, so the id is attacker-controlled.
     * Refusal must be a toast, not an exception: `findOrFail` here surfaced as
     * the app's full-page 404 overlay on top of an otherwise working screen.
     */
    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $foreign->id)
        ->assertSet('creatingId', null)
        ->assertDispatched('toast');

    expect($foreign->fresh()->user_id)->toBeNull();
});

test('an employee who already has an account is refused', function () {
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $existing = User::factory()->create(['tenant_id' => $hr->tenant_id]);
    $employee = Employee::factory()->create(['tenant_id' => $hr->tenant_id, 'user_id' => $existing->id]);

    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $employee->id)
        ->assertSet('creatingId', null)
        ->assertDispatched('toast');
});

test('row actions work when the tenant context is EMPTY', function () {
    /*
     * ─────────────────────────────────────────────────────────────────────
     * THE REGRESSION THIS FILE PREVIOUSLY MISSED
     *
     * `TenantContext` is filled by the `tenant.context` middleware, attached
     * to the tenant route group. Livewire's own POST /livewire/update endpoint
     * is registered by the package OUTSIDE that group and runs with ['web']
     * only — so on a real Livewire action the context is EMPTY and
     * `getTenantId()` returns null. A query scoped to `tenant_id = null`
     * matches nothing, and `findOrFail` then threw a 404 over the whole page.
     *
     * Every other test here calls actingAsTenantUser(), which sets the context
     * directly in-process. That kept the suite green while both row buttons
     * were broken in the browser.
     *
     * Clearing the context reproduces the real request shape.
     * ─────────────────────────────────────────────────────────────────────
     */
    $hr = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $hr->tenant_id,
        'user_id' => null,
        'job_title' => 'فني صيانة',
    ]);

    app(\App\Domain\Tenancy\TenantContext::class)->setTenant(null);

    Livewire::test(NonAccountEmployees::class)
        ->call('viewDetails', $employee->id)
        ->assertSet('viewingId', $employee->id)
        ->assertNotDispatched('toast');

    app(\App\Domain\Tenancy\TenantContext::class)->setTenant(null);

    Livewire::test(NonAccountEmployees::class)
        ->call('startCreate', $employee->id)
        ->assertSet('creatingId', $employee->id)
        ->assertNotDispatched('toast');
});
