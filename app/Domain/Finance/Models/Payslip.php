<?php

namespace App\Domain\Finance\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\PayBasis;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * One employee's pay for one run.
 *
 * Everything from `employee_name` through `worked_minutes` is a frozen
 * snapshot (BR-608): a locked payslip renders from its own columns alone,
 * without joining employees, employee_contracts or work_ledger_entries.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $payroll_run_id
 * @property int $employee_id
 * @property int|null $employee_contract_id
 * @property string $employee_name
 * @property string|null $job_title
 * @property string|null $department_name
 * @property PayBasis $pay_basis
 * @property int $base_rate
 * @property string $pay_currency
 * @property int $period_scheduled_days
 * @property int $scheduled_days
 * @property int $present_days
 * @property int $excused_days
 * @property int $absent_days
 * @property int $worked_minutes
 * @property int $base_amount
 * @property int $absence_deduction
 * @property int $allowances_total
 * @property int $deductions_total
 * @property int $gross_amount
 * @property int $net_amount
 */
class Payslip extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'pay_basis' => PayBasis::class,
            'base_rate' => 'integer',
            'period_scheduled_days' => 'integer',
            'scheduled_days' => 'integer',
            'present_days' => 'integer',
            'excused_days' => 'integer',
            'absent_days' => 'integer',
            'worked_minutes' => 'integer',
            'base_amount' => 'integer',
            'absence_deduction' => 'integer',
            'allowances_total' => 'integer',
            'deductions_total' => 'integer',
            'gross_amount' => 'integer',
            'net_amount' => 'integer',
        ];
    }

    /**
     * A payslip is locked by its run, never independently (BR-610).
     */
    public function isLocked(): bool
    {
        return (bool) $this->payrollRun?->isLocked();
    }

    /**
     * @return BelongsTo<PayrollRun, $this>
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
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
     * @return HasMany<PayslipLineItem, $this>
     */
    public function lineItems(): HasMany
    {
        return $this->hasMany(PayslipLineItem::class);
    }
}
