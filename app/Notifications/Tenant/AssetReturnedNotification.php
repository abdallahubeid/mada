<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\AssetAssignment;
use Illuminate\Support\Facades\Route;

class AssetReturnedNotification extends TenantNotification
{
    public function __construct(
        public Asset $asset,
        public AssetAssignment $assignment,
        public AssetStatus $nextStatus,
    ) {
        $this->assignment->loadMissing(['employee']);
    }

    protected function title(): string
    {
        return match ($this->nextStatus) {
            AssetStatus::UnderMaintenance => 'إعادة أصل للصيانة',
            AssetStatus::Retired => 'استبعاد أصل بعد الإعادة',
            default => 'إعادة أصل',
        };
    }

    protected function message(): string
    {
        $code = $this->asset->asset_code;
        $employee = $this->assignment->employee?->full_name ?? 'موظف';
        $status = $this->nextStatus->label();

        return "أُعيد الأصل {$code} من «{$employee}». الحالة الجديدة: {$status}.";
    }

    protected function url(): ?string
    {
        return Route::has('tenant.assets.index')
            ? route('tenant.assets.index')
            : null;
    }

    protected function icon(): string
    {
        return 'asset';
    }

    protected function severity(): string
    {
        return match ($this->nextStatus) {
            AssetStatus::Retired, AssetStatus::UnderMaintenance => 'high',
            default => 'medium',
        };
    }

    protected function type(): string
    {
        return 'asset.returned';
    }
}
