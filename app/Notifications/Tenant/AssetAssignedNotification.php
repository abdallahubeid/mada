<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\AssetAssignment;
use Illuminate\Support\Facades\Route;

class AssetAssignedNotification extends TenantNotification
{
    public function __construct(
        public Asset $asset,
        public AssetAssignment $assignment,
    ) {
        $this->assignment->loadMissing(['employee']);
    }

    protected function title(): string
    {
        return 'إسناد أصل';
    }

    protected function message(): string
    {
        $employee = $this->assignment->employee?->full_name ?? 'موظف';

        return "أُسند الأصل {$this->asset->asset_code} إلى «{$employee}».";
    }

    protected function url(): ?string
    {
        return Route::has('tenant.assets.index') ? route('tenant.assets.index') : null;
    }

    protected function icon(): string
    {
        return 'asset';
    }

    protected function severity(): string
    {
        return 'medium';
    }

    protected function type(): string
    {
        return 'asset.assigned';
    }
}
