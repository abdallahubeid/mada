<?php

namespace App\Domain\Finance\Enums;

/**
 * Payroll run lifecycle (BR-603, ADR-09).
 *
 * draft -> pending_approval -> approved (locked) -> paid
 * Rejection returns pending_approval to draft with a reason.
 */
enum PayrollRunStatus: string
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
            self::Approved => 'معتمد',
            self::Paid => 'مدفوع',
            self::Cancelled => 'ملغى',
        };
    }

    /**
     * Approved and paid runs are immutable — observers reject every write
     * to the run, its payslips and their line items (BR-610, NFR-11).
     */
    public function isLocked(): bool
    {
        return $this === self::Approved || $this === self::Paid;
    }

    /**
     * Only a draft accepts edits to payslips and line items.
     */
    public function isEditable(): bool
    {
        return $this === self::Draft;
    }

    /**
     * Only finalized runs reach the Financial Dashboard (BR-607).
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
