<?php

namespace App\Domain\Tenancy\Enums;

enum AssetCondition: string
{
    case New = 'new';
    case Good = 'good';
    case Fair = 'fair';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::Good => 'جيد',
            self::Fair => 'مقبول',
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
