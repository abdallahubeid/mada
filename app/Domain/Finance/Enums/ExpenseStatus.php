<?php

namespace App\Domain\Finance\Enums;

/**
 * Expense claim lifecycle (BR-613).
 *
 * draft -> pending_approval -> approved -> paid
 *                           -> rejected (terminal; a new claim is required)
 */
enum ExpenseStatus: string
{
    case Draft = 'draft';
    case PendingApproval = 'pending_approval';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Paid = 'paid';

    public function label(): string
    {
        return match ($this) {
            self::Draft => 'مسودة',
            self::PendingApproval => 'بانتظار الاعتماد',
            self::Approved => 'معتمد',
            self::Rejected => 'مرفوض',
            self::Paid => 'مصروف',
        };
    }

    /**
     * Only a draft or a rejected claim accepts edits.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft || $this === self::Rejected;
    }

    /**
     * Approved and paid claims are financial records — no edits, no deletes.
     */
    public function isLocked(): bool
    {
        return $this === self::Approved || $this === self::Paid;
    }

    /**
     * Only approved/paid expenses reach the Financial Dashboard (BR-607).
     */
    public function countsTowardDashboard(): bool
    {
        return $this->isLocked();
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
