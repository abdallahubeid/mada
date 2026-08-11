<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\WorkLedgerDayType;
use App\Domain\Tenancy\Enums\WorkLedgerSource;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

/**
 * One reconciled employee-day in the Work Ledger (ADR-21, MODULES.md §4.2).
 *
 * The sole source of absence deductions (BR-602/BR-404). This is a derived
 * projection: it carries NO SoftDeletes, because rebuilds hard-delete and
 * re-insert the period (BR-406). That is the single documented exception to
 * NFR-10, permitted only because every row is reconstructible from Work
 * Calendar + Attendance + approved Leave.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property Carbon $date
 * @property WorkLedgerDayType $day_type
 * @property WorkLedgerSource $source
 * @property int|null $attendance_id
 * @property int|null $leave_request_id
 * @property int|null $worked_minutes
 */
class WorkLedgerEntry extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'date',
        'day_type',
        'source',
        'attendance_id',
        'leave_request_id',
        'worked_minutes',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'date' => 'date',
            'day_type' => WorkLedgerDayType::class,
            'source' => WorkLedgerSource::class,
            'worked_minutes' => 'integer',
        ];
    }

    /**
     * Only an Absent day produces a payroll deduction (BR-404).
     */
    public function isDeductible(): bool
    {
        return $this->day_type->isDeductible();
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Attendance, $this>
     */
    public function attendance(): BelongsTo
    {
        return $this->belongsTo(Attendance::class);
    }

    /**
     * @return BelongsTo<LeaveRequest, $this>
     */
    public function leaveRequest(): BelongsTo
    {
        return $this->belongsTo(LeaveRequest::class);
    }
}
