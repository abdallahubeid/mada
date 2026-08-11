<?php

namespace App\Domain\Tenancy\Enums;

enum ContractStatus: string
{
    case Active = 'active';
    case Expired = 'expired';
    case Terminated = 'terminated';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'ساري',
            self::Expired => 'منتهٍ',
            self::Terminated => 'منهي',
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
