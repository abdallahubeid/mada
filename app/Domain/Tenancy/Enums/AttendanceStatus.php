<?php

namespace App\Domain\Tenancy\Enums;

enum AttendanceStatus: string
{
    case Present = 'present';
    case Late = 'late';
    case Absent = 'absent';
    case HalfDay = 'half_day';

    public function label(): string
    {
        return match ($this) {
            self::Present => 'حاضر',
            self::Late => 'متأخر',
            self::Absent => 'غائب',
            self::HalfDay => 'نصف يوم',
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
