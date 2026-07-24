<?php

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('spatie permission teams are configured to use tenant_id', function () {
    expect(config('permission.teams'))->toBeTrue()
        ->and(config('permission.column_names.team_foreign_key'))->toBe('tenant_id');
});

test('tenant context has no tenant bound by default', function () {
    $context = app(TenantContext::class);

    expect($context->hasTenant())->toBeFalse()
        ->and($context->getTenant())->toBeNull()
        ->and($context->getTenantId())->toBeNull();
});

test('binding a tenant updates the tenant context', function () {
    $tenant = Tenant::factory()->active()->create();

    $context = app(TenantContext::class);
    $context->setTenant($tenant);

    expect($context->hasTenant())->toBeTrue()
        ->and($context->getTenant()->is($tenant))->toBeTrue()
        ->and($context->getTenantId())->toBe($tenant->id);
});

test('binding a tenant synchronizes the spatie permissions team id', function () {
    $tenant = Tenant::factory()->active()->create();

    app(TenantContext::class)->setTenant($tenant);

    expect(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBe($tenant->id);
});

test('clearing the tenant context also clears the spatie permissions team id', function () {
    $tenant = Tenant::factory()->active()->create();
    $context = app(TenantContext::class);

    $context->setTenant($tenant);
    $context->setTenant(null);

    expect($context->hasTenant())->toBeFalse()
        ->and(app(PermissionRegistrar::class)->getPermissionsTeamId())->toBeNull();
});
