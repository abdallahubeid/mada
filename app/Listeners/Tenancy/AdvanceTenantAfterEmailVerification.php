<?php

namespace App\Listeners\Tenancy;

use App\Domain\Tenancy\Actions\VerifyTenantEmailAction;
use App\Models\User;
use Illuminate\Auth\Events\Verified;

/**
 * Bridges Laravel's `Verified` event to the tenant lifecycle (BR-202).
 *
 * Listening to the event rather than branching inside VerifyEmailController is
 * what makes the transition hold for every path that verifies an address — the
 * signed link, a resent link, and any future operator-side confirmation — not
 * just the one controller action that happens to exist today.
 */
class AdvanceTenantAfterEmailVerification
{
    public function __construct(private readonly VerifyTenantEmailAction $action) {}

    public function handle(Verified $event): void
    {
        $user = $event->user;

        // Platform operators have no tenant, and the event carries the generic
        // MustVerifyEmail contract rather than our User.
        if (! $user instanceof User || $user->tenant_id === null) {
            return;
        }

        $tenant = $user->tenant;

        if ($tenant === null) {
            return;
        }

        $this->action->handle($tenant, $user);
    }
}
