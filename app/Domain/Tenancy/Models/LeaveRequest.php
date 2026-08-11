<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use App\Models\User;
use Database\Factories\LeaveRequestFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property int $leave_type_id
 * @property Carbon $start_date
 * @property Carbon $end_date
 * @property int $days_count
 * @property string|null $reason
 * @property LeaveRequestStatus $status
 * @property bool $requires_manager_escalation
 * @property int $approval_level
 * @property int $current_approval_level
 * @property int|null $approved_by
 * @property string|null $rejection_reason
 */
class LeaveRequest extends Model
{
    /** @use HasFactory<LeaveRequestFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): LeaveRequestFactory
    {
        return LeaveRequestFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'leave_type_id',
        'start_date',
        'end_date',
        'days_count',
        'reason',
        'status',
        'requires_manager_escalation',
        'approval_level',
        'current_approval_level',
        'approved_by',
        'rejection_reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'requires_manager_escalation' => false,
        'approval_level' => 1,
        'current_approval_level' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
            'days_count' => 'integer',
            'status' => LeaveRequestStatus::class,
            'requires_manager_escalation' => 'boolean',
            'approval_level' => 'integer',
            'current_approval_level' => 'integer',
        ];
    }

    public function needsFurtherApproval(): bool
    {
        if (! $this->requires_manager_escalation) {
            return false;
        }

        return $this->current_approval_level < max(1, $this->approval_level);
    }

    public function coversDate(?Carbon $date = null): bool
    {
        $date ??= now()->startOfDay();

        return $date->greaterThanOrEqualTo($this->start_date->copy()->startOfDay())
            && $date->lessThanOrEqualTo($this->end_date->copy()->endOfDay());
    }

    public static function calculateDaysCount(Carbon|string $start, Carbon|string $end): int
    {
        $startDate = Carbon::parse($start)->startOfDay();
        $endDate = Carbon::parse($end)->startOfDay();

        if ($endDate->lt($startDate)) {
            return 0;
        }

        $holidays = OfficialHoliday::query()->get();
        $count = 0;

        for ($date = $startDate->copy(); $date->lte($endDate); $date->addDay()) {
            if (! OfficialHoliday::isHoliday($date, $holidays)) {
                $count++;
            }
        }

        return $count;
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<LeaveType, $this>
     */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /**
     * Approval history via the generic engine (ADR-08).
     *
     * The bespoke `approval_level` / `current_approval_level` /
     * `requires_manager_escalation` columns above are retained for now and are
     * migrated into `approvals` and dropped as a discrete follow-up (BR-901).
     *
     * @return MorphMany<Approval, $this>
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * The single open approval, if any.
     *
     * Relies on BR-904: a subject may have at most one non-terminal approval
     * at a time, guarded inside the creating transaction.
     *
     * @return MorphOne<Approval, $this>
     */
    public function currentApproval(): MorphOne
    {
        return $this->morphOne(Approval::class, 'approvable')
            ->where('status', ApprovalStatus::Pending->value);
    }
}
