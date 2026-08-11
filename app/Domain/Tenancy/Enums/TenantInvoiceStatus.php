<?php

namespace App\Domain\Tenancy\Enums;

enum TenantInvoiceStatus: string
{
    case Paid = 'paid';
    case Pending = 'pending';
    case Failed = 'failed';

    public function label(): string
    {
        return match ($this) {
            self::Paid => 'مدفوعة',
            self::Pending => 'معلّقة',
            self::Failed => 'فشل الدفع',
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
