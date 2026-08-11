<?php

namespace App\Domain\Tenancy\Enums;

/**
 * Employment lifecycle status for HR employee profiles (non-payroll).
 */
enum EmployeeStatus: string
{
    case Active = 'active';
    case OnLeave = 'on_leave';
    case Resigned = 'resigned';
    case Suspended = 'suspended';

    public function label(): string
    {
        return match ($this) {
            self::Active => 'نشط',
            self::OnLeave => 'في إجازة',
            self::Resigned => 'مستقيل',
            self::Suspended => 'موقوف',
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
