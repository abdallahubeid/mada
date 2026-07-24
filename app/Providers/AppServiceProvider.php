<?php

namespace App\Providers;

use App\Domain\Tenancy\TenantContext;
use App\Models\Setting;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\View;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        // Bound as a singleton so the resolved tenant persists for the
        // lifetime of a single request. See docs/ARCHITECTURE.md §1.2.
        $this->app->singleton(TenantContext::class);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        View::composer('*', function ($view): void {
            $settings = Schema::hasTable('settings')
                ? Setting::query()->pluck('value', 'key')->toArray()
                : [];

            $view->with('settings', $settings);
        });
    }
}
