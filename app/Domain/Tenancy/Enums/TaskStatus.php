<?php

namespace App\Domain\Tenancy\Enums;

enum TaskStatus: string
{
    case Todo = 'todo';
    case InProgress = 'in_progress';
    case Review = 'review';
    case Completed = 'completed';

    public function label(): string
    {
        return match ($this) {
            self::Todo => 'لتنفيذه',
            self::InProgress => 'قيد التنفيذ',
            self::Review => 'قيد المراجعة',
            self::Completed => 'مكتمل',
        };
    }

    public function previous(): ?self
    {
        $values = self::values();
        $index = array_search($this->value, $values, true);

        return $index > 0 ? self::from($values[$index - 1]) : null;
    }

    public function next(): ?self
    {
        $values = self::values();
        $index = array_search($this->value, $values, true);

        return isset($values[$index + 1]) ? self::from($values[$index + 1]) : null;
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
