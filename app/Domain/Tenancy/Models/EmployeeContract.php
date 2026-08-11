<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Enums\PayBasis;
use Database\Factories\EmployeeContractFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Employment contract record.
 *
 * Carries two independent axes (ADR-19): `contract_type` describes the
 * employment *form* and has no pay semantics; `pay_basis` is the sole input to
 * pay computation (BR-301). Neither is ever derived from the other.
 *
 * Monetary rates are unsigned minor units — halalas/cents (ADR-20, BR-609).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property ContractType $contract_type
 * @property PayBasis $pay_basis
 * @property int $base_rate
 * @property int|null $billing_rate
 * @property string|null $pay_currency
 * @property Carbon $start_date
 * @property Carbon|null $end_date
 * @property Carbon|null $probation_end_date
 * @property ContractStatus $status
 * @property string|null $notes
 */
class EmployeeContract extends Model
{
    /** @use HasFactory<EmployeeContractFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): EmployeeContractFactory
    {
        return EmployeeContractFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'contract_type',
        'pay_basis',
        'base_rate',
        'billing_rate',
        'pay_currency',
        'start_date',
        'end_date',
        'probation_end_date',
        'status',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'active',
        'pay_basis' => 'salaried',
        'base_rate' => 0,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'contract_type' => ContractType::class,
            'pay_basis' => PayBasis::class,
            'base_rate' => 'integer',
            'billing_rate' => 'integer',
            'status' => ContractStatus::class,
            'start_date' => 'date',
            'end_date' => 'date',
            'probation_end_date' => 'date',
        ];
    }

    /**
     * A payable contract with no rate set yet.
     *
     * The pay-fields migration backfilled every existing contract to
     * salaried/0, which is safe but silent. Payroll run generation uses this to
     * refuse to open a run while any active contract is unpriced, rather than
     * producing a run full of zero-pay payslips (BR-301a).
     */
    public function hasUnsetPayRate(): bool
    {
        return $this->pay_basis->requiresBaseRate() && $this->base_rate <= 0;
    }

    public function isExpiringSoon(int $withinDays = 30): bool
    {
        if ($this->end_date === null || $this->status !== ContractStatus::Active) {
            return false;
        }

        $today = now()->startOfDay();
        $limit = now()->addDays($withinDays)->endOfDay();

        return $this->end_date->greaterThanOrEqualTo($today)
            && $this->end_date->lessThanOrEqualTo($limit);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
