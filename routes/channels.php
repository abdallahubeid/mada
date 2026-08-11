<?php

use App\Broadcasting\ConversationChannel;
use App\Broadcasting\TenantNotificationChannel;
use Illuminate\Support\Facades\Broadcast;

Broadcast::channel('admin.notifications', function ($user) {
    return $user !== null && $user->canAccessPlatformConsole();
});

Broadcast::channel('tenant.{tenantId}.notifications.{userId}', TenantNotificationChannel::class);

/*
 * Messenger threads. Unlike the notification channel above — which compares
 * two integers — this one performs a database lookup per subscribe to verify
 * participation. A user opening the messenger subscribes once per visible
 * thread, so this is the query to watch if the thread list ever grows large.
 */
Broadcast::channel('tenant.{tenantId}.conversations.{conversationId}', ConversationChannel::class);
