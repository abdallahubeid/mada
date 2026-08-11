<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class SubscriptionLimitApproaching
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public string $resource,
        public int $used,
        public int $limit,
        public float $percent,
        public string $label,
    ) {}
}
