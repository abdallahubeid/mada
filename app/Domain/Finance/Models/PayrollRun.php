<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A payroll run — the maker-checker unit of work (BR-603, ADR-09).
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $period
 * @property Carbon $period_start
 * @property Carbon $period_end
 * @property PayrollRunStatus $status
 * @property string $currency
 * @property string|null $active_period
 * @property int|null $maker_id
 * @property int|null $approver_id
 * @property Carbon|null $approved_at
 * @property Carbon|null $paid_at
 * @property string|null $rejection_reason
 * @property int $total_base
 * @property int $total_absence_deduction
 * @property int $total_allowances
 * @property int $total_deductions
 * @property int $total_gross
 * @property int $total_net
 * @property int $payslip_count
 */
class PayrollRun extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * Maintained by the database as a stored generated column — never written
     * by the application. Listed here so mass-assignment cannot target it.
     *
     * @var list<string>
     */
    protected $guarded = ['id', 'active_period'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'period_start' => 'date',
            'period_end' => 'date',
            'status' => PayrollRunStatus::class,
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'total_base' => 'integer',
            'total_absence_deduction' => 'integer',
            'total_allowances' => 'integer',
            'total_deductions' => 'integer',
            'total_gross' => 'integer',
            'total_net' => 'integer',
            'payslip_count' => 'integer',
        ];
    }

    /**
     * Approved and paid runs are immutable (BR-610, NFR-11).
     */
    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /**
     * Separation of duties: the approver may never be the maker (BR-615).
     *
     * This cannot be enforced by permissions — the Owner Gate::before bypass
     * grants Owners `finance.payroll.prepare` implicitly — so it lives here.
     */
    public function canBeApprovedBy(User $user): bool
    {
        return $this->status === PayrollRunStatus::PendingApproval
            && $this->maker_id !== $user->id;
    }

    /**
     * @return HasMany<Payslip, $this>
     */
    public function payslips(): HasMany
    {
        return $this->hasMany(Payslip::class);
    }

    /**
     * Corrections this run CARRIES for earlier locked runs (BR-603).
     *
     * @return HasMany<PayrollRunAdjustment, $this>
     */
    public function adjustments(): HasMany
    {
        return $this->hasMany(PayrollRunAdjustment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function maker(): BelongsTo
    {
        return $this->belongsTo(User::class, 'maker_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
