<?php

namespace App\Domain\Finance\Models;

use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Domain\Tenancy\Models\Approval;
use App\Domain\Tenancy\Models\Employee;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * An operational or claimable expense (BR-613).
 *
 * Decisions live in the generic `approvals` table (ADR-08); `status` here is a
 * denormalised mirror so listing and filtering need no join.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int|null $expense_category_id
 * @property int|null $employee_id
 * @property int|null $submitted_by
 * @property string $title
 * @property string|null $description
 * @property Carbon $expense_date
 * @property int $amount
 * @property string $currency
 * @property bool $is_claimable
 * @property string|null $receipt_path
 * @property ExpenseStatus $status
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property string|null $rejection_reason
 * @property Carbon|null $paid_at
 */
class Expense extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $guarded = ['id'];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'draft',
        'is_claimable' => true,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'expense_date' => 'date',
            'amount' => 'integer',
            'is_claimable' => 'boolean',
            'status' => ExpenseStatus::class,
            'decided_at' => 'datetime',
            'paid_at' => 'datetime',
        ];
    }

    public function isLocked(): bool
    {
        return $this->status->isLocked();
    }

    /**
     * Only a claimable expense is owed back to someone. A non-claimable cost
     * was already settled directly, so `paid` would be meaningless for it.
     */
    public function isDisbursable(): bool
    {
        return $this->is_claimable && $this->status === ExpenseStatus::Approved;
    }

    /**
     * @return BelongsTo<ExpenseCategory, $this>
     */
    public function category(): BelongsTo
    {
        return $this->belongsTo(ExpenseCategory::class, 'expense_category_id');
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
    public function submitter(): BelongsTo
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decider(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }

    /**
     * @return MorphMany<Approval, $this>
     */
    public function approvals(): MorphMany
    {
        return $this->morphMany(Approval::class, 'approvable');
    }

    /**
     * @return MorphOne<Approval, $this>
     */
    public function currentApproval(): MorphOne
    {
        return $this->morphOne(Approval::class, 'approvable')
            ->where('status', ApprovalStatus::Pending->value);
    }
}
