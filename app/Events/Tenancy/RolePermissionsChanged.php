<?php

namespace App\Events\Tenancy;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class RolePermissionsChanged
{
    use Dispatchable, SerializesModels;

    /**
     * @param  'created'|'updated'|'deleted'  $action
     */
    public function __construct(
        public int $tenantId,
        public string $roleName,
        public string $action,
        public ?int $actorUserId = null,
    ) {}
}
