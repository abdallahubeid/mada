<?php

use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.notifications', function ($user) {
    return $user !== null && $user->canAccessPlatformConsole();
});
