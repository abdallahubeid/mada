<?php

namespace App\Services\Marketing;

use Illuminate\Support\Facades\Cache;

/**
 * Busts public marketing caches after Super Admin CMS writes
 * (docs/ADMIN_CMS_ANALYSIS.md §4.1).
 */
class MarketingCache
{
    public const PAGE_HOME = 'marketing.page.home';

    /**
     * @var list<string>
     */
    public const METRIC_KEYS = [
        'marketing.metrics.active_tenants',
        'marketing.metrics.active_users',
    ];

    public static function flush(): void
    {
        Cache::forget(self::PAGE_HOME);

        foreach (self::METRIC_KEYS as $key) {
            Cache::forget($key);
        }
    }
}
