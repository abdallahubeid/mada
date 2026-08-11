<?php

namespace App\Broadcasting;

use App\Models\User;

/**
 * Authorizes private tenant notification streams:
 * `tenant.{tenantId}.notifications.{userId}`
 */
class TenantNotificationChannel
{
    public function join(User $user, int|string $tenantId, int|string $userId): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }

        return (int) $user->id === (int) $userId
            && (int) $user->tenant_id === (int) $tenantId;
    }
}
