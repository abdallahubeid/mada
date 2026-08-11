<?php

namespace App\Domain\Tenancy\Enums;

enum WorkLedgerDayType: string
{
    /** Scheduled working day, NOT yet reconciled — sentinel only (BR-405). */
    case Workday = 'workday';

    case Weekend = 'weekend';
    case Holiday = 'holiday';

    /** Covered by an approved leave request (BR-401). */
    case Excused = 'excused';

    case Present = 'present';

    /** Scheduled, no attendance, no leave — the only deductible type (BR-404). */
    case Absent = 'absent';

    public function label(): string
    {
        return match ($this) {
            self::Workday => 'يوم عمل (غير مُسوّى)',
            self::Weekend => 'عطلة أسبوعية',
            self::Holiday => 'عطلة رسمية',
            self::Excused => 'إجازة معتمدة',
            self::Present => 'حضور',
            self::Absent => 'غياب',
        };
    }

    /**
     * Only Absent produces a payroll deduction (BR-404).
     */
    public function isDeductible(): bool
    {
        return $this === self::Absent;
    }

    /**
     * A period containing any unresolved day cannot be paid (BR-405).
     */
    public function isResolved(): bool
    {
        return $this !== self::Workday;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
