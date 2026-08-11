<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Database\Factories\OfficialHolidayFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Official company holiday (optionally recurring by month/day).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property bool $is_recurring
 * @property string|null $notes
 */
class OfficialHoliday extends Model
{
    /** @use HasFactory<OfficialHolidayFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): OfficialHolidayFactory
    {
        return OfficialHolidayFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'start_date',
        'end_date',
        'is_recurring',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'is_recurring' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'is_recurring' => 'boolean',
        ];
    }

    public function coversDate(Carbon $date): bool
    {
        $day = $date->copy()->startOfDay();

        if (! $this->is_recurring) {
            return $day->betweenIncluded(
                $this->start_date->copy()->startOfDay(),
                $this->end_date->copy()->startOfDay(),
            );
        }

        $start = $this->start_date->copy()->year($day->year)->startOfDay();
        $end = $this->end_date->copy()->year($day->year)->startOfDay();

        if ($end->lt($start)) {
            return $day->gte($start) || $day->lte($end);
        }

        return $day->betweenIncluded($start, $end);
    }

    /**
     * @param  Collection<int, self>|null  $holidays
     */
    public static function isHoliday(Carbon $date, ?Collection $holidays = null): bool
    {
        $holidays ??= static::query()->get();

        return $holidays->contains(
            fn (self $holiday): bool => $holiday->coversDate($date)
        );
    }
}
