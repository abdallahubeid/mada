<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A pending or decided approval attached to any approvable subject (ADR-08).
 *
 * This model records approval *state* only. It never mutates its subject's
 * domain state — the subject's own listener does that (BR-906), which is what
 * keeps the engine free of per-module knowledge.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $approvable_type
 * @property int $approvable_id
 * @property ApprovalStatus $status
 * @property int $level
 * @property int $current_level
 * @property int|null $requested_by
 * @property int|null $decided_by
 * @property Carbon|null $decided_at
 * @property string|null $reason
 */
class Approval extends Model
{
    use BelongsToTenant, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'approvable_type',
        'approvable_id',
        'status',
        'level',
        'current_level',
        'requested_by',
        'decided_by',
        'decided_at',
        'reason',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => 'pending',
        'level' => 1,
        'current_level' => 1,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => ApprovalStatus::class,
            'level' => 'integer',
            'current_level' => 'integer',
            'decided_at' => 'datetime',
        ];
    }

    /**
     * A decision at the final level is terminal; below it the chain advances
     * and the approval stays pending (BR-903).
     */
    public function isFinalLevel(): bool
    {
        return $this->current_level >= max(1, $this->level);
    }

    public function isOpen(): bool
    {
        return ! $this->status->isTerminal();
    }

    /**
     * @return MorphTo<Model, $this>
     */
    public function approvable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function decidedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'decided_by');
    }
}
