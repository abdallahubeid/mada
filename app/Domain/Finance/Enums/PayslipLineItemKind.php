<?php

namespace App\Domain\Finance\Enums;

/**
 * Grouping metadata for a payslip line item, and the guard on its sign.
 *
 * Amounts are stored as their effect on net pay (ADR-20): an allowance is
 * positive, a deduction is negative.
 */
enum PayslipLineItemKind: string
{
    case Allowance = 'allowance';
    case Deduction = 'deduction';

    public function label(): string
    {
        return match ($this) {
            self::Allowance => 'بدل',
            self::Deduction => 'استقطاع',
        };
    }

    /**
     * Whether a signed amount is consistent with this kind.
     *
     * Zero is permitted for both — a configured line may legitimately net out.
     */
    public function permits(int $amount): bool
    {
        return match ($this) {
            self::Allowance => $amount >= 0,
            self::Deduction => $amount <= 0,
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
