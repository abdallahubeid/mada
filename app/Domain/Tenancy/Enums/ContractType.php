<?php

namespace App\Domain\Tenancy\Enums;

enum ContractType: string
{
    case FullTime = 'full_time';
    case PartTime = 'part_time';
    case FixedTerm = 'fixed_term';
    case Freelance = 'freelance';

    public function label(): string
    {
        return match ($this) {
            self::FullTime => 'دوام كامل',
            self::PartTime => 'دوام جزئي',
            self::FixedTerm => 'عقد محدد المدة',
            self::Freelance => 'عمل حر',
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
