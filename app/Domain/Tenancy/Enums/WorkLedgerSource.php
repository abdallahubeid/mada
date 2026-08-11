<?php

namespace App\Domain\Tenancy\Enums;

/**
 * Which reconciliation input produced a Work Ledger day classification.
 */
enum WorkLedgerSource: string
{
    case WorkCalendar = 'work_calendar';
    case OfficialHoliday = 'official_holiday';
    case Attendance = 'attendance';
    case LeaveRequest = 'leave_request';

    /** Audited manual correction by a manager (BR-305). */
    case Manual = 'manual';

    public function label(): string
    {
        return match ($this) {
            self::WorkCalendar => 'تقويم العمل',
            self::OfficialHoliday => 'العطلات الرسمية',
            self::Attendance => 'سجل الحضور',
            self::LeaveRequest => 'طلب إجازة',
            self::Manual => 'تعديل يدوي',
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
