<?php

namespace App\Domain\Tenancy\Enums;

/**
 * How an employee is paid (ADR-19).
 *
 * Independent of {@see ContractType}, which describes the employment *form*
 * and carries no pay semantics. Neither is ever derived from the other.
 */
enum PayBasis: string
{
    case Salaried = 'salaried';
    case Hourly = 'hourly';
    case Unpaid = 'unpaid';

    public function label(): string
    {
        return match ($this) {
            self::Salaried => 'راتب ثابت',
            self::Hourly => 'أجر بالساعة',
            self::Unpaid => 'بدون أجر',
        };
    }

    /**
     * Unpaid contracts must carry a zero base_rate (BR-301a).
     */
    public function requiresBaseRate(): bool
    {
        return $this !== self::Unpaid;
    }

    /**
     * Only hourly pay multiplies work_ledger_entries.worked_minutes.
     */
    public function usesWorkedMinutes(): bool
    {
        return $this === self::Hourly;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
