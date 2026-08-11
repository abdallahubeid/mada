<?php

namespace App\Domain\Finance\Enums;

/**
 * Offboarding settlement lifecycle (BR-606).
 *
 * Mirrors the payroll run's maker-checker discipline: a settlement is a
 * permanent financial record and locks on approval.
 */
enum SettlementStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Paid = 'paid';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::PendingApproval => 'بانتظار الاعتماد',
            self::Approved => 'معتمدة',
            self::Paid => 'مصروفة',
            self::Cancelled => 'ملغاة',
        };
    }

    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    public function isLocked(): bool
    {
        return $this === self::Approved || $this === self::Paid;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
