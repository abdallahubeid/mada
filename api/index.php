<?php

/*
|--------------------------------------------------------------------------
| Vercel entry point (vercel-php runtime)
|--------------------------------------------------------------------------
|
| A Vercel function runs on a read-only filesystem; only /tmp is writable.
| Every path Laravel writes to (storage, the generated bootstrap caches and
| compiled views) is therefore moved under /tmp/laravel.
|
| The defaults below apply only when the variable is NOT set in the Vercel
| project, so any of them can be overridden from the dashboard without a
| code change. Database credentials are never defaulted here: they must be
| set as project environment variables.
|
| What a serverless function cannot do, and how each is handled:
| - No long-running processes: no queue worker (QUEUE_CONNECTION=sync runs
|   jobs inline) and no Reverb server (BROADCAST_CONNECTION=null; the front
|   end skips Echo when VITE_REVERB_APP_KEY is absent).
| - Nothing written to disk survives: sessions and cache live in MySQL so
|   every function instance sees the same login and the same rate limits.
|
*/

use Illuminate\Http\Request;

// Absolute /tmp, not sys_get_temp_dir(): Laravel only treats a path as
// absolute when it starts with "/", anything else is joined to base_path().
$tmp = '/tmp/laravel';

foreach ([
    'storage/app/private',
    'storage/app/public',
    'storage/app/chat',
    'storage/framework/cache/data',
    'storage/framework/sessions',
    'storage/framework/views',
    'storage/logs',
    'bootstrap/cache',
] as $directory) {
    if (! is_dir($path = $tmp.'/'.$directory)) {
        @mkdir($path, 0777, true);
    }
}

$defaults = [
    'LARAVEL_STORAGE_PATH' => $tmp.'/storage',
    'VIEW_COMPILED_PATH' => $tmp.'/storage/framework/views',
    'APP_PACKAGES_CACHE' => $tmp.'/bootstrap/cache/packages.php',
    'APP_SERVICES_CACHE' => $tmp.'/bootstrap/cache/services.php',
    'APP_CONFIG_CACHE' => $tmp.'/bootstrap/cache/config.php',
    'APP_ROUTES_CACHE' => $tmp.'/bootstrap/cache/routes-v7.php',
    'APP_EVENTS_CACHE' => $tmp.'/bootstrap/cache/events.php',

    'APP_ENV' => 'production',
    'APP_DEBUG' => 'false',
    'LOG_CHANNEL' => 'stderr',
    'LOG_LEVEL' => 'error',

    'DB_CONNECTION' => 'mysql',
    'SESSION_DRIVER' => 'database',
    'SESSION_SECURE_COOKIE' => 'true',
    'CACHE_STORE' => 'database',
    'QUEUE_CONNECTION' => 'sync',
    'BROADCAST_CONNECTION' => 'null',
];

foreach ($defaults as $key => $value) {
    if (getenv($key) === false) {
        putenv("{$key}={$value}");
        $_ENV[$key] = $_SERVER[$key] = $value;
    }
}

define('LARAVEL_START', microtime(true));

require __DIR__.'/../vendor/autoload.php';

(require_once __DIR__.'/../bootstrap/app.php')
    ->handleRequest(Request::capture());
