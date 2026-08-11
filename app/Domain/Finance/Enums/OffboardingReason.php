<?php

namespace App\Domain\Finance\Enums;

use App\Services\Finance\OffboardingCalculator;

/**
 * Why the employment ended.
 *
 * Drives the resulting employee status and, in most jurisdictions, the EOSB
 * entitlement — a resignation and a termination are not treated alike. The
 * multiplier lives on {@see OffboardingCalculator}, which
 * documents the rates it applies.
 */
enum OffboardingReason: string
{
    case Resignation = 'resignation';
    case Termination = 'termination';
    case ContractEnd = 'contract_end';
    case Retirement = 'retirement';

    public function label(): string
    {
        return match ($this) {
            self::Resignation => 'استقالة',
            self::Termination => 'إنهاء خدمة',
            self::ContractEnd => 'انتهاء العقد',
            self::Retirement => 'تقاعد',
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
