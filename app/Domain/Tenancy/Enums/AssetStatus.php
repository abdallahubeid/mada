<?php

namespace App\Domain\Tenancy\Enums;

enum AssetStatus: string
{
    case Available = 'available';
    case Assigned = 'assigned';
    case UnderMaintenance = 'under_maintenance';
    case Retired = 'retired';

    public function label(): string
    {
        return match ($this) {
            self::Available => 'متاح',
            self::Assigned => 'مُسند',
            self::UnderMaintenance => 'تحت الصيانة',
            self::Retired => 'مستبعد',
        };
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
