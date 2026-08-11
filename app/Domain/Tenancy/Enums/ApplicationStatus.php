<?php

namespace App\Domain\Tenancy\Enums;

enum ApplicationStatus: string
{
    case New = 'new';
    case UnderReview = 'under_review';
    case Interviewed = 'interviewed';
    case Accepted = 'accepted';
    case Rejected = 'rejected';

    public function label(): string
    {
        return match ($this) {
            self::New => 'جديد',
            self::UnderReview => 'قيد المراجعة',
            self::Interviewed => 'تمت المقابلة',
            self::Accepted => 'مقبول',
            self::Rejected => 'مرفوض',
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
