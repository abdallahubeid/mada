<?php

namespace App\Domain\Tenancy\Enums;

enum AnnouncementType: string
{
    case Info = 'info';
    case Warning = 'warning';
    case Event = 'event';
    case Urgent = 'urgent';

    public function label(): string
    {
        return match ($this) {
            self::Info => 'معلومة',
            self::Warning => 'تنبيه',
            self::Event => 'فعالية',
            self::Urgent => 'عاجل',
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
