<?php

use App\Domain\Platform\PlatformPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

test('unauthorized admin route renders the custom 403 page', function () {
    actingAsPlatformOperator(PlatformPermissionCatalog::ROLE_CONTENT_MANAGER);

    $this->get(route('admin.tenants'))
        ->assertForbidden()
        ->assertSee('غير مصرح بالوصول', false)
        ->assertSee('العودة للوحة التحكم', false)
        ->assertSee('العودة للخلف', false)
        ->assertSee('Access Denied', false);
});

test('the 403 page links platform operators to their preferred admin home', function () {
    actingAsPlatformOperator(PlatformPermissionCatalog::ROLE_CONTENT_MANAGER);

    $home = route(auth()->user()->preferredAdminHomeRoute());

    $this->get(route('admin.tenants'))
        ->assertForbidden()
        ->assertSee($home, false);
});

test('guest 403 page falls back to the landing home link', function () {
    $html = view('errors.403')->render();

    expect($html)
        ->toContain('غير مصرح بالوصول')
        ->toContain('العودة للرئيسية')
        ->toContain(route('landing'));
});
