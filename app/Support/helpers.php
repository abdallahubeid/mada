<?php

use App\Support\FlashNotifier;

if (! function_exists('flash')) {
    function flash(): FlashNotifier
    {
        return app(FlashNotifier::class);
    }
}
