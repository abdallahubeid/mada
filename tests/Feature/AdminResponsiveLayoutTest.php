<?php

use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    actingAsPlatformOperator();
});

test('admin layout hides the sidebar off-canvas with RTL-safe transforms below lg', function () {
    $html = $this->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('id="admin-sidebar"')
        ->toContain('aria-label="فتح القائمة"')
        ->toContain('lg:hidden')
        ->toContain('fixed inset-y-0 start-0')
        ->toContain('-translate-x-full')
        ->toContain('rtl:translate-x-full')
        ->toContain('lg:static')
        ->toContain('lg:translate-x-0')
        ->toContain('lg:rtl:translate-x-0')
        ->toContain('closeSidebarDrawer()')
        ->toContain('w-full min-w-0 flex-1')
        ->toContain('overflow-y-auto')
        ->toContain('overflow-x-hidden')
        ->toContain('aria-label="إغلاق القائمة"')
        ->not->toContain('fixed inset-y-0 end-0');
});

test('admin main content column is full width and does not reserve closed-drawer space', function () {
    $html = $this->get(route('admin.dashboard'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('flex h-full min-h-0 w-full')
        ->toContain('flex min-h-0 w-full min-w-0 flex-1 flex-col')
        ->toContain('max-lg:pointer-events-none')
        ->not->toContain('fixed inset-y-0 end-0');
});

test('admin cms index tables are wrapped for horizontal scrolling', function () {
    $html = $this->get(route('admin.problems.index'))
        ->assertOk()
        ->getContent();

    expect($html)
        ->toContain('overflow-x-auto')
        ->toContain('w-full')
        ->toContain('<table');
});
