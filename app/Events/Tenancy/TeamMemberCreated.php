<?php

namespace App\Events\Tenancy;

use App\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class TeamMemberCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public User $member,
        public string $roleName,
    ) {}
}
