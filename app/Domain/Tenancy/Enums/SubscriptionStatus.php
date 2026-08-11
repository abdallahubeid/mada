<?php

namespace App\Domain\Tenancy\Enums;

enum SubscriptionStatus: string
{
    case Active = 'active';
    case Trial = 'trial';
    case Expired = 'expired';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::Trial => 'تجريبي',
            self::Expired => 'منتهٍ',
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
