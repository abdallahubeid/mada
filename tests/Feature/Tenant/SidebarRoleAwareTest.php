<?php

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('owner sees hr management collapsed into a single dropdown', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => TenantStatus::Active->value]);

    $html = $this->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->toContain('قسم الموارد البشرية');
    // Flat HR item labels should NOT appear as standalone top-level rows —
    // they only exist inside the dropdown's children list now.
    expect(substr_count($html, 'الأقسام'))->toBe(1);
});

test('hr manager sees hr management as flat top-level items', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_HR_MANAGER, ['status' => TenantStatus::Active->value]);

    // /app/dashboard dispatches by role — follow through to the HR dashboard
    // this viewer actually lands on, and assert the sidebar rendered there.
    $html = $this->followingRedirects()->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->not->toContain('قسم الموارد البشرية');
    expect($html)->toContain('الأقسام');
    expect($html)->toContain('الموظفين');
});

test('employee sees flat self-service links and no hr management group', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => TenantStatus::Active->value]);

    $html = $this->followingRedirects()->get('/app/dashboard')->assertOk()->getContent();

    expect($html)->toContain('تقييماتي');
    expect($html)->toContain('تسجيل الحضور والانصراف');
    expect($html)->toContain('طلبات الإجازة');
    expect($html)->not->toContain('قسم الموارد البشرية');
    expect($html)->not->toContain('الأقسام');
});
