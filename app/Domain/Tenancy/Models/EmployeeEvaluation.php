<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use App\Domain\Tenancy\Enums\EvaluationStatus;
use Database\Factories\EmployeeEvaluationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Hierarchical employee performance evaluation for a configured period.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $employee_id
 * @property int|null $evaluator_id
 * @property EvaluationPeriodType $period_type
 * @property string $period_key
 * @property string|null $rating
 * @property string|null $notes
 * @property EvaluationStatus $status
 */
class EmployeeEvaluation extends Model
{
    /** @use HasFactory<EmployeeEvaluationFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): EmployeeEvaluationFactory
    {
        return EmployeeEvaluationFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'employee_id',
        'evaluator_id',
        'period_type',
        'period_key',
        'rating',
        'notes',
        'status',
    ];

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
            'period_type' => EvaluationPeriodType::class,
            'status' => EvaluationStatus::class,
            'rating' => 'decimal:2',
        ];
    }

    public function isLocked(): bool
    {
        return $this->status === EvaluationStatus::Approved;
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /**
     * @return BelongsTo<Employee, $this>
     */
    public function evaluator(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'evaluator_id');
    }
}
