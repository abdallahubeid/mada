<?php

namespace App\Domain\Finance\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A correction to a locked payroll run, carried by a LATER run (BR-603).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $payroll_run_id
 * @property int|null $original_payslip_id
 * @property string $original_period
 * @property string $employee_name
 * @property int $employee_id
 * @property string $reason
 * @property int $amount
 * @property int|null $created_by
 */
class PayrollRunAdjustment extends Model
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
            'amount' => 'integer',
        ];
    }

    public function isClawback(): bool
    {
        return $this->amount < 0;
    }

    /**
     * The run carrying the correction.
     *
     * @return BelongsTo<PayrollRun, $this>
     */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /**
     * The locked payslip being corrected.
     *
     * @return BelongsTo<Payslip, $this>
     */
    public function originalPayslip(): BelongsTo
    {
        return $this->belongsTo(Payslip::class, 'original_payslip_id');
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function author(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
