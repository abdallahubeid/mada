<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\LeaveRequest;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A pending leave request reached a terminal decision (approved or rejected).
 *
 * Fires only on the final decision — an escalation to the next approval level
 * leaves the request pending and does not dispatch this event.
 */
class LeaveRequestDecided
{
    use Dispatchable, SerializesModels;

    /**
     * @param  'approved'|'rejected'  $decision
     */
    public function __construct(
        public LeaveRequest $leaveRequest,
        public string $decision,
        public ?int $actorUserId = null,
    ) {}
}
