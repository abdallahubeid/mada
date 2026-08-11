<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Support\EosbPolicy;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Final settlement on termination (BR-606).
 *
 * Every figure is frozen at generation, for the same reason a payslip is
 * (BR-608): this is a permanent financial record and must render identically
 * forever, without joining a contract or leave balance that has since changed.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property int|null $employee_contract_id
 * @property string $employee_name
 * @property PayBasis $pay_basis
 * @property int $base_rate
 * @property string $currency
 * @property Carbon|null $joining_date
 * @property Carbon $last_working_day
 * @property int $service_months
 * @property int $unused_leave_days
 * @property array<string, mixed>|null $eosb_policy
 * @property OffboardingReason $reason
 * @property SettlementStatus $status
 * @property int $eosb_amount
 * @property int $leave_payout_amount
 * @property int $prorated_salary_amount
 * @property int $loan_deduction_amount
 * @property int $other_deduction_amount
 * @property int $total_amount
 * @property string|null $active_employee_id
 */
class OffboardingSettlement extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * Maintained by the database as a stored generated column — never written
     * by the application. Guarded so mass assignment cannot target it.
     *
     * @var list<string>
     */
    protected $guarded = ['id', 'active_employee_id'];

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
            'pay_basis' => PayBasis::class,
            'reason' => OffboardingReason::class,
            'status' => SettlementStatus::class,
            'joining_date' => 'date',
            'last_working_day' => 'date',
            'base_rate' => 'integer',
            'service_months' => 'integer',
            'unused_leave_days' => 'integer',
            'eosb_policy' => 'array',
            'eosb_amount' => 'integer',
            'leave_payout_amount' => 'integer',
            'prorated_salary_amount' => 'integer',
            'loan_deduction_amount' => 'integer',
            'other_deduction_amount' => 'integer',
            'total_amount' => 'integer',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /**
     * Separation of duties, same rule as payroll (BR-615): the user who
     * prepared a settlement may not also approve it.
     */
    public function canBeApprovedBy(User $user): bool
    {
        return $this->status === SettlementStatus::PendingApproval
            && $this->created_by !== $user->id;
    }

    /**
     * The EOSB rules this settlement was actually computed under.
     *
     * Rows generated before `eosb_policy` existed read back as the defaults,
     * which is what they were in fact computed under — the rules were not
     * configurable then.
     */
    public function eosbPolicy(): EosbPolicy
    {
        return EosbPolicy::fromArray($this->eosb_policy ?? []);
    }

    public function serviceYearsLabel(): string
    {
        $years = intdiv($this->service_months, 12);
        $months = $this->service_months % 12;

        return $years > 0
            ? "{$years} سنة و {$months} شهر"
            : "{$months} شهر";
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<EmployeeContract, $this>
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(EmployeeContract::class, 'employee_contract_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approver_id');
    }
}
