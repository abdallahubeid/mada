<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Carbon;

class SubscriptionRenewalDue
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public Carbon $renewsAt,
        public int $daysRemaining,
    ) {}
}
