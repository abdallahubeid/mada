<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

/**
 * Clear the landing video setting that points at the removed placeholder.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * DELETING THE FILE IS NOT ENOUGH — THE PATH LIVES IN THE DATABASE
 *
 * `public/media/mada-product-tour.mp4` was stock footage standing in for a
 * product tour, and it has been removed from the repo. But the path it was
 * reachable at was seeded into `settings.previews_video`, so an environment
 * that already ran `SettingSeeder` still holds the string.
 *
 * `x-marketing.product-video` only checks that a source is CONFIGURED, not
 * that it resolves — which is correct, since the source may legitimately be a
 * CDN URL it cannot stat. So a stale row renders a full <video> band whose
 * <source> 404s: a silent failed request on every landing-page view and, on
 * browsers that reserve space before decoding, a blank slab between the hero
 * and the next section.
 *
 * Re-running the seeder would also fix it, but seeders are not part of a
 * deploy and re-running this one would overwrite every CMS edit the admin has
 * made since. This migration touches exactly the one dangling row.
 * ─────────────────────────────────────────────────────────────────────────
 *
 * SCOPED TO THE PLACEHOLDER PATH, not to the key. An admin who has since
 * uploaded a real video keeps it.
 */
return new class extends Migration
{
    private const PLACEHOLDER = 'media/mada-product-tour.mp4';

    public function up(): void
    {
        /*
         * `settings` is a key/value store shared by the landing CMS. The guard
         * matters for a fresh install where migrations run before any seeder:
         * the table exists, but querying a key that was never written is a
         * no-op rather than an error — the `where` simply matches nothing.
         */
        if (! Schema::hasTable('settings')) {
            return;
        }

        $cleared = DB::table('settings')
            ->where('key', 'previews_video')
            ->where('value', self::PLACEHOLDER)
            ->update(['value' => '', 'updated_at' => now()]);

        if ($cleared > 0 && app()->runningInConsole()) {
            echo '  Cleared placeholder landing video setting.'.PHP_EOL;
        }
    }

    /**
     * Deliberately NOT reversible.
     *
     * Restoring the value would point the public landing page at a file that
     * no longer exists in the repository — a rollback whose only effect is to
     * reintroduce a 404. There is nothing to recover: the setting held a path,
     * not content, and the path is dead.
     */
    public function down(): void
    {
        // No-op on purpose. See the note above.
    }
};
