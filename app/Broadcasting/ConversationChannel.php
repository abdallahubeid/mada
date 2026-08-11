<?php

namespace App\Broadcasting;

use App\Domain\Messaging\Models\Conversation;
use App\Models\User;

/**
 * Authorizes `tenant.{tenantId}.conversations.{conversationId}`.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * MEMBERSHIP, NOT ROLE
 *
 * This is where the privacy policy is actually enforced. An Owner, an HR
 * Manager and a platform Super Admin are refused here exactly like anyone
 * else, because the only question asked is "is there a participant row for
 * this user in this conversation".
 *
 * That matters specifically because `AppServiceProvider` registers a
 * `Gate::before` that grants Owners and Super Admins every ability. Any check
 * phrased as `$user->can(...)` would therefore return true for them and hand
 * over every conversation in the company. No Gate is consulted here, and none
 * should be added.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY THE TENANT IS CHECKED SEPARATELY FROM MEMBERSHIP
 *
 * `Conversation` carries the global tenant scope, which resolves the tenant
 * from `TenantContext`. Broadcasting authorization runs on a route where that
 * context is bound from the authenticated user, so the scope would already
 * restrict the lookup — but relying on it alone would make this class correct
 * only for as long as that middleware ordering holds. The scope is dropped and
 * both facts are asserted explicitly, so this is self-contained.
 * ─────────────────────────────────────────────────────────────────────────
 */
class ConversationChannel
{
    public function join(User $user, int|string $tenantId, int|string $conversationId): bool
    {
        if ($user->tenant_id === null) {
            return false;
        }

        if ((int) $user->tenant_id !== (int) $tenantId) {
            return false;
        }

        $conversation = Conversation::withoutGlobalScopes()
            ->whereKey((int) $conversationId)
            ->where('tenant_id', (int) $tenantId)
            ->first();

        if ($conversation === null) {
            return false;
        }

        return $conversation->includes($user);
    }
}
