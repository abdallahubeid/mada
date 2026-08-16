<?php

use App\Domain\Tenancy\Middleware\BindTenantContext;
use App\Domain\Tenancy\Middleware\EnsureTenantActive;
use App\Http\Middleware\EnsurePlatformOperator;
use App\Http\Middleware\TouchLastSeen;
use Illuminate\Foundation\Application;
use Illuminate\Foundation\Configuration\Exceptions;
use Illuminate\Foundation\Configuration\Middleware;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use Spatie\Permission\Middleware\PermissionMiddleware;
use Spatie\Permission\Middleware\RoleMiddleware;
use Spatie\Permission\Middleware\RoleOrPermissionMiddleware;

return Application::configure(basePath: dirname(__DIR__))
    ->withRouting(
        web: __DIR__.'/../routes/web.php',
        commands: __DIR__.'/../routes/console.php',
        channels: __DIR__.'/../routes/channels.php',
        health: '/up',
        then: function (): void {
            Route::middleware(['web', 'auth', 'platform.operator'])
                ->prefix('admin')
                ->name('admin.')
                ->group(base_path('routes/admin.php'));

            // Tenant app (auth) + public company portal share routes/tenant.php.
            // Auth middleware is applied inside the file so /companies/{slug} stays public.
            Route::middleware('web')
                ->group(base_path('routes/tenant.php'));
        },
    )
    ->withMiddleware(function (Middleware $middleware): void {
        /*
         * Render (and any comparable PaaS) terminates TLS at its load balancer
         * and forwards plain HTTP to the container. Without this, Laravel sees
         * the inbound scheme as `http` and every `url()`, `route()`, redirect
         * and asset link comes out as `http://` on an `https://` page —
         * mixed-content blocking, and a login POST that silently downgrades.
         *
         * `at: '*'` rather than an IP list because the proxy addresses are
         * dynamic and not published; the container is only reachable through
         * that proxy, so there is no path for a client to forge the header.
         */
        $middleware->trustProxies(at: '*');

        $middleware->alias([
            'tenant.context' => BindTenantContext::class,
            'tenant.active' => EnsureTenantActive::class,
            'platform.operator' => EnsurePlatformOperator::class,
            'role' => RoleMiddleware::class,
            'permission' => PermissionMiddleware::class,
            'role_or_permission' => RoleOrPermissionMiddleware::class,
            'presence.touch' => TouchLastSeen::class,
        ]);
    })
    ->withExceptions(function (Exceptions $exceptions): void {
        $exceptions->shouldRenderJsonWhen(
            fn (Request $request) => $request->is('api/*'),
        );
    })->create();
