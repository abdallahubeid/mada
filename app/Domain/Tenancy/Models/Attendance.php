<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\AttendanceStatus;
use Database\Factories\AttendanceFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Daily attendance log for an employee.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property Carbon $date
 * @property Carbon|null $check_in
 * @property Carbon|null $check_out
 * @property AttendanceStatus $status
 * @property string|null $notes
 */
class Attendance extends Model
{
    /** @use HasFactory<AttendanceFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * Default late threshold (HH:MM) when org calendar has no override.
     */
    public const DEFAULT_LATE_THRESHOLD = '09:00';

    protected static function newFactory(): AttendanceFactory
    {
        return AttendanceFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'date',
        'check_in',
        'check_out',
        'status',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'present',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'check_in' => 'datetime',
            'check_out' => 'datetime',
            'status' => AttendanceStatus::class,
        ];
    }

    public function workedHoursLabel(): string
    {
        if ($this->check_in === null || $this->check_out === null) {
            return '—';
        }

        $minutes = (int) abs($this->check_in->diffInMinutes($this->check_out));
        $hours = intdiv($minutes, 60);
        $remainder = $minutes % 60;

        return sprintf('%d:%02d', $hours, $remainder);
    }

    public static function resolveCheckInStatus(Carbon $checkIn, ?string $threshold = null): AttendanceStatus
    {
        $threshold ??= self::resolveLateThreshold();
        [$hour, $minute] = array_map('intval', explode(':', $threshold));
        $deadline = $checkIn->copy()->setTime($hour, $minute, 0);

        return $checkIn->greaterThan($deadline)
            ? AttendanceStatus::Late
            : AttendanceStatus::Present;
    }

    /**
     * Prefer tenant work schedule (start + grace); fall back to 09:00.
     */
    public static function resolveLateThreshold(): string
    {
        $calendar = WorkCalendar::query()->first();

        if ($calendar === null) {
            return self::DEFAULT_LATE_THRESHOLD;
        }

        return $calendar->lateThreshold();
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
