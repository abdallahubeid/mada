<?php

namespace App\Domain\Tenancy\Enums;

enum EvaluationPeriodType: string
{
    case Monthly = 'monthly';
    case Quarterly = 'quarterly';
    case SemiAnnually = 'semi_annually';
    case Annually = 'annually';

    public function label(): string
    {
        return match ($this) {
            self::Monthly => 'شهري',
            self::Quarterly => 'ربع سنوي',
            self::SemiAnnually => 'نصف سنوي',
            self::Annually => 'سنوي',
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
