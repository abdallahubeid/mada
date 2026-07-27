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

    public const PRODUCT_PREVIEW_STATS = 'marketing.product_preview.stats';

    /**
     * @var list<string>
     */
    public const METRIC_KEYS = [
        'marketing.metrics.active_tenants',
        'marketing.metrics.active_users',
        self::PRODUCT_PREVIEW_STATS,
    ];

    public static function flush(): void
    {
        Cache::forget(self::PAGE_HOME);

        foreach (self::METRIC_KEYS as $key) {
            Cache::forget($key);
        }
    }
}
