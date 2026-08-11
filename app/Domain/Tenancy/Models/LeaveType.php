<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\LeaveRequestStatus;
use Database\Factories\LeaveTypeFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property int $annual_days
 * @property bool $requires_approval
 */
class LeaveType extends Model
{
    /** @use HasFactory<LeaveTypeFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): LeaveTypeFactory
    {
        return LeaveTypeFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'annual_days',
        'requires_approval',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'annual_days' => 14,
        'requires_approval' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'annual_days' => 'integer',
            'requires_approval' => 'boolean',
        ];
    }

    public function remainingDaysFor(int $employeeId, ?int $year = null): int
    {
        $year ??= (int) now()->year;

        $used = (int) LeaveRequest::query()
            ->where('employee_id', $employeeId)
            ->where('leave_type_id', $this->id)
            ->where('status', LeaveRequestStatus::Approved)
            ->whereYear('start_date', $year)
            ->sum('days_count');

        return max(0, $this->annual_days - $used);
    }

    /**
     * @return HasMany<LeaveRequest, $this>
     */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }
}
