<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Symfony\Component\HttpFoundation\Response;

/**
 * Records that the signed-in user is currently active.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * PRESENCE IS DERIVED FROM REQUESTS, NOT FROM A SOCKET
 *
 * The obvious implementation is an Echo presence channel, and it is the wrong
 * one here: Reverb is not running in this deployment, so a socket-derived
 * "online" would report everybody offline forever. A timestamp touched by
 * ordinary traffic works with or without the websocket, and it survives the
 * tab being closed without a disconnect event.
 *
 * It also means "online" answers a slightly different question — "used the
 * ERP in the last two minutes" rather than "has a socket open" — which is
 * closer to what a colleague actually wants to know.
 *
 * THROTTLED THROUGH THE CACHE. Presence has two-minute resolution, so writing
 * on every request would be one UPDATE per page view, per user, for no extra
 * accuracy. One write per 55 seconds keeps a user inside
 * ConversationPresence::ONLINE_WITHIN_MINUTES with a full beat to spare.
 *
 * `withoutTimestamps` + `saveQuietly`: this is bookkeeping, not a change the
 * user made. Bumping `updated_at` would make every profile look edited on
 * every page load, and firing model events would put an observer in the path
 * of every authenticated request.
 * ─────────────────────────────────────────────────────────────────────────
 */
class TouchLastSeen
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user !== null) {
            $key = 'presence:touched:'.$user->getKey();

            if (! Cache::has($key)) {
                Cache::put($key, true, now()->addSeconds(55));

                $user::withoutTimestamps(
                    fn () => $user->forceFill(['last_seen_at' => now()])->saveQuietly()
                );
            }
        }

        return $next($request);
    }
}
