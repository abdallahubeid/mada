<?php

namespace App\Domain\Tenancy\Enums;

enum ApprovalStatus: string
{
    case Pending = 'pending';
    case Approved = 'approved';
    case Rejected = 'rejected';
    case Cancelled = 'cancelled';

    public function label(): string
    {
        return match ($this) {
            self::Pending => 'قيد الانتظار',
            self::Approved => 'معتمد',
            self::Rejected => 'مرفوض',
            self::Cancelled => 'ملغى',
        };
    }

    /**
     * Only Pending permits a further decision (BR-903).
     */
    public function isTerminal(): bool
    {
        return $this !== self::Pending;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
