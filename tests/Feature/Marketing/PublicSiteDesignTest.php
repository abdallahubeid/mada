<?php

use Database\Seeders\SettingSeeder;
use Database\Seeders\TestimonialSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

/*
 * The public site wears the Andromeda identity (docs/DESIGN_SYSTEM.md §1.3).
 * These pin the parts that are easy to lose in a later edit: the scope that
 * keeps the identity off the app, the shared chrome, and the accessibility
 * hooks on the interactive pieces.
 */

dataset('public pages', [
    'home' => ['landing'],
    'about' => ['marketing.about'],
    'features' => ['marketing.features'],
    'solutions' => ['marketing.solutions'],
    'pricing' => ['marketing.pricing'],
    'security' => ['marketing.security'],
    'faq' => ['marketing.faq'],
    'contact' => ['marketing.contact'],
    'privacy' => ['marketing.privacy'],
    'terms' => ['marketing.terms'],
]);

test('every public page renders inside the site scope with the shared chrome', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('<body class="site', false)
        ->assertSee('site-header', false)
        ->assertSee('aria-controls="site-mobile-menu"', false)
        ->assertSee('site-container', false);
})->with('public pages');

test('inner pages carry the cream banner with a breadcrumb back home', function (string $route, string $crumb) {
    $html = $this->get(route($route))->assertOk()->getContent();

    expect($html)
        ->toContain('site-panel--flat')
        ->toContain('aria-label="مسار التنقّل"')
        ->toContain('href="'.route('landing').'"')
        ->toContain('<li aria-current="page">'.$crumb.'</li>');
})->with([
    'contact names the page, not its slogan' => ['marketing.contact', 'تواصل معنا'],
    'legal pages name themselves' => ['marketing.privacy', 'سياسة الخصوصية'],
]);

test('the guest auth screens share the site identity', function (string $route) {
    $this->get(route($route))
        ->assertOk()
        ->assertSee('<body class="site', false)
        ->assertSee('site-panel--flat', false)
        ->assertSee('site-card', false)
        ->assertSee('site-input', false)
        ->assertSee('site-btn site-btn-primary', false);
})->with([
    'sign in' => ['login'],
    'register' => ['register'],
    'forgot password' => ['password.request'],
]);

test('the guest auth screens are pinned to one viewport with no page scroll', function (string $route) {
    // Body = the dynamic viewport, clipped; the panel area is the only thing
    // that may scroll, and only when a screen is shorter than the form.
    $this->get(route($route))
        ->assertOk()
        ->assertSee('<body class="site site-auth flex h-dvh flex-col overflow-hidden', false)
        ->assertSee('overflow-y-auto overscroll-contain', false)
        ->assertSee('mx-auto my-auto w-full', false);
})->with([
    'sign in' => ['login'],
    'register' => ['register'],
]);

test('sign-in errors are tied to their fields for assistive technology', function () {
    $this->from(route('login'))
        ->post(route('login'), [])
        ->assertRedirect(route('login'));

    $this->get(route('login'))
        ->assertOk()
        ->assertSee('aria-invalid="true" aria-describedby="email-error"', false)
        ->assertSee('id="email-error"', false);
});

test('the register terms checkbox links to the real legal pages', function () {
    $this->get(route('register'))
        ->assertOk()
        ->assertSee('href="'.route('marketing.terms').'"', false)
        ->assertSee('href="'.route('marketing.privacy').'"', false);
});

test('the site identity does not leak into the authenticated console', function () {
    actingAsPlatformOperator();

    $this->get(route('admin.dashboard'))
        ->assertOk()
        ->assertDontSee('<body class="site', false)
        ->assertDontSee('site-header', false);
});

test('the active nav link is announced to assistive technology', function () {
    $html = $this->get(route('marketing.pricing'))->assertOk()->getContent();

    expect($html)
        ->toMatch('/href="\/pricing"[^>]*aria-current="page"\s*>الأسعار/u')
        ->not->toMatch('/href="\/about"[^>]*aria-current="page"/u');
});

test('the auto-advancing testimonial slider can be paused', function () {
    $this->seed([SettingSeeder::class, TestimonialSeeder::class]);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('aria-roledescription="carousel"', false)
        ->assertSee('aria-label="تشغيل التبديل التلقائي"', false)
        ->assertSee('aria-label="عرض الرأي 3"', false);
});

test('section titles keep rendering the CMS emphasis marker', function () {
    $this->seed(SettingSeeder::class);

    $this->get(route('landing'))
        ->assertOk()
        ->assertSee('site-section-title', false)
        ->assertSee('mada-underline-double', false);
});
