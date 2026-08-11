<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use Database\Seeders\PlanSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;

uses(RefreshDatabase::class);

/**
 * The tenant listing's search box, plan select and pagination.
 *
 * All three were decorative markup: the inputs carried no `name`, sat outside
 * any form, and the plan options were three hardcoded slugs, so a tenant on any
 * other plan was unreachable. The listing also rendered every tenant on one
 * page with no paginator at all.
 */
function listedTenant(string $name, string $status = 'active', ?int $planId = null, ?string $email = null): Tenant
{
    $tenant = Tenant::factory()->create([
        'name' => $name,
        'slug' => Str::slug($name).'-'.uniqid(),
        'status' => $status,
        'plan_id' => $planId,
    ]);

    User::factory()->create([
        'tenant_id' => $tenant->id,
        'email' => $email ?? 'owner-'.uniqid().'@example.test',
    ]);

    return $tenant;
}

test('search matches the tenant name', function () {
    actingAsPlatformOperator();
    $match = listedTenant('مؤسسة البيان');
    listedTenant('شركة الفرات');

    $response = $this->get(route('admin.tenants', ['status' => 'active', 'q' => 'البيان']))->assertOk();

    expect($response->viewData('tenants'))->toHaveCount(1)
        ->and($response->viewData('tenants')->first()['slug'])->toBe($match->slug);
});

test('search matches the slug and a user email', function () {
    actingAsPlatformOperator();
    $bySlug = listedTenant('Northwind Trading');
    $byEmail = listedTenant('Contoso', 'active', null, 'finance@contoso.test');

    $slugHit = $this->get(route('admin.tenants', ['status' => 'active', 'q' => $bySlug->slug]))->assertOk();
    expect($slugHit->viewData('tenants'))->toHaveCount(1);

    $emailHit = $this->get(route('admin.tenants', ['status' => 'active', 'q' => 'finance@contoso']))->assertOk();
    expect($emailHit->viewData('tenants'))->toHaveCount(1)
        ->and($emailHit->viewData('tenants')->first()['slug'])->toBe($byEmail->slug);
});

test('the status tab counts narrow to the search as well as the rows', function () {
    actingAsPlatformOperator();
    listedTenant('مؤسسة البيان');
    listedTenant('مؤسسة البيان الثانية');
    listedTenant('شركة الفرات');

    $response = $this->get(route('admin.tenants', ['status' => 'active', 'q' => 'البيان']))->assertOk();

    // A tab badge reading "3" above two filtered rows would look like the
    // filter had silently failed.
    expect($response->viewData('counts')[TenantStatus::Active->value])->toBe(2)
        ->and($response->viewData('counts')['all'])->toBe(2);
});

test('a search matching nothing yields an empty page rather than everything', function () {
    actingAsPlatformOperator();
    listedTenant('مؤسسة البيان');

    $response = $this->get(route('admin.tenants', ['status' => 'active', 'q' => 'لا-يوجد-مثل-هذا']))->assertOk();

    expect($response->viewData('tenants'))->toHaveCount(0);
});

test('the plan filter uses real plan ids and offers every seeded plan', function () {
    actingAsPlatformOperator();
    $this->seed(PlanSeeder::class);

    $startup = Plan::query()->where('slug', 'startup')->firstOrFail();
    $growth = Plan::query()->where('slug', 'growth')->firstOrFail();

    $onStartup = listedTenant('Alpha Co', 'active', $startup->id);
    listedTenant('Beta Co', 'active', $growth->id);

    $response = $this->get(route('admin.tenants', ['status' => 'active', 'plan' => $startup->id]))->assertOk();

    expect($response->viewData('tenants'))->toHaveCount(1)
        ->and($response->viewData('tenants')->first()['slug'])->toBe($onStartup->slug)
        // The select used to hardcode startup/growth/enterprise, so a tenant on
        // any other plan could not be filtered for at all.
        ->and($response->viewData('plans')->pluck('id'))->toContain($growth->id);
});

test('a non-numeric plan filter is ignored rather than fatal', function () {
    actingAsPlatformOperator();
    listedTenant('Alpha Co');

    $response = $this->get(route('admin.tenants', ['status' => 'active', 'plan' => 'startup']))->assertOk();

    expect($response->viewData('tenants'))->toHaveCount(1);
});

test('the listing paginates instead of rendering every tenant at once', function () {
    actingAsPlatformOperator();

    for ($i = 0; $i < 25; $i++) {
        listedTenant("Tenant {$i}");
    }

    $response = $this->get(route('admin.tenants', ['status' => 'active']))->assertOk();
    $paginator = $response->viewData('tenants');

    expect($paginator->total())->toBe(25)
        ->and($paginator)->toHaveCount(20)
        ->and($paginator->hasPages())->toBeTrue();

    expect($this->get(route('admin.tenants', ['status' => 'active', 'page' => 2]))->viewData('tenants'))
        ->toHaveCount(5);
});

test('paging keeps the active search and status', function () {
    actingAsPlatformOperator();

    for ($i = 0; $i < 22; $i++) {
        listedTenant("Falcon {$i}");
    }
    listedTenant('Unrelated Co');

    $page2 = $this->get(route('admin.tenants', ['status' => 'active', 'q' => 'Falcon', 'page' => 2]))->assertOk();

    // Without withQueryString the paginator links drop the filters and page 2
    // silently shows an unfiltered slice.
    expect($page2->viewData('tenants'))->toHaveCount(2)
        ->and($page2->viewData('tenants')->url(1))->toContain('q=Falcon')
        ->and($page2->viewData('tenants')->url(1))->toContain('status=active');
});

test('a rejected tenant renders an Arabic badge rather than the raw enum value', function () {
    actingAsPlatformOperator();
    $tenant = Tenant::factory()->create([
        'name' => 'شركة مرفوضة',
        'slug' => 'marfooda-'.uniqid(),
        'status' => TenantStatus::Rejected,
        'rejection_reason' => 'السجل التجاري غير مكتمل.',
    ]);

    // The status-badge map had no `rejected` entry, so it fell through to the
    // default branch and printed the raw English value in an Arabic console.
    $this->get(route('admin.tenants', ['status' => 'rejected']))
        ->assertOk()
        ->assertSee('مرفوض', false)
        ->assertDontSee('>rejected<', false);

    $this->get(route('admin.tenants.show', $tenant->slug))
        ->assertOk()
        ->assertSee('السجل التجاري غير مكتمل.', false);
});
