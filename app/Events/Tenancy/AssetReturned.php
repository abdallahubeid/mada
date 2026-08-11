<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\AssetAssignment;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AssetReturned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Asset $asset,
        public AssetAssignment $assignment,
        public AssetStatus $nextStatus,
        public ?int $actorUserId = null,
    ) {}
}
