<?php

namespace App\Events\Tenancy;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamMemberDeactivated
{
    use Dispatchable, SerializesModels;

    /**
     * @param  'deactivated'|'deleted'  $action
     */
    public function __construct(
        public User $member,
        public string $action = 'deactivated',
    ) {}
}
