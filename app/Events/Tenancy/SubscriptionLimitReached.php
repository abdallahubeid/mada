<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionLimitReached
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $resource,
        public int $used,
        public int $limit,
        public string $label,
    ) {}
}
