<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Per-tenant work calendar (working days, weekends, schedule, holidays JSON).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property list<int> $working_days
 * @property string|null $work_start_time
 * @property string|null $work_end_time
 * @property int $grace_period_minutes
 * @property list<int>|null $weekend_days
 * @property list<array{date: string, name: string}> $holidays
 * @property int|null $created_by
 * @property int|null $updated_by
 */
class WorkCalendar extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'working_days',
        'work_start_time',
        'work_end_time',
        'grace_period_minutes',
        'weekend_days',
        'holidays',
        'created_by',
        'updated_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'name' => 'Default',
        'grace_period_minutes' => 15,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'working_days' => 'array',
            'weekend_days' => 'array',
            'holidays' => 'array',
            'grace_period_minutes' => 'integer',
        ];
    }

    public function workStartTimeLabel(): string
    {
        return $this->formatTimeValue($this->work_start_time) ?? '08:30';
    }

    public function workEndTimeLabel(): string
    {
        return $this->formatTimeValue($this->work_end_time) ?? '16:30';
    }

    /**
     * Late threshold = work start + grace period (HH:MM).
     */
    public function lateThreshold(): string
    {
        $start = Carbon::createFromFormat('H:i', $this->workStartTimeLabel());

        return $start->addMinutes(max(0, (int) $this->grace_period_minutes))->format('H:i');
    }

    /**
     * @return list<int>
     */
    public function resolvedWeekendDays(): array
    {
        if (is_array($this->weekend_days) && $this->weekend_days !== []) {
            return array_values(array_map('intval', $this->weekend_days));
        }

        $working = is_array($this->working_days) ? array_map('intval', $this->working_days) : [];

        return array_values(array_diff(range(0, 6), $working));
    }

    private function formatTimeValue(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value->format('H:i');
        }

        return substr((string) $value, 0, 5);
    }
}
