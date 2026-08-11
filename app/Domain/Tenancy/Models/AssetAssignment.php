<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\AssetCondition;
use App\Models\User;
use Database\Factories\AssetAssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Custody assignment of an asset to an employee.
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $asset_id
 * @property int $employee_id
 * @property Carbon $assigned_at
 * @property Carbon|null $returned_at
 * @property AssetCondition $condition_on_assign
 * @property AssetCondition|null $condition_on_return
 * @property string|null $notes
 * @property int|null $assigned_by
 */
class AssetAssignment extends Model
{
    /** @use HasFactory<AssetAssignmentFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'tenant_asset_assignments';

    protected static function newFactory(): AssetAssignmentFactory
    {
        return AssetAssignmentFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'asset_id',
        'employee_id',
        'assigned_at',
        'returned_at',
        'condition_on_assign',
        'condition_on_return',
        'notes',
        'assigned_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'condition_on_assign' => 'good',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'returned_at' => 'datetime',
            'condition_on_assign' => AssetCondition::class,
            'condition_on_return' => AssetCondition::class,
        ];
    }

    public function isActive(): bool
    {
        return $this->returned_at === null;
    }

    /**
     * @return BelongsTo<Asset, $this>
     */
    public function asset(): BelongsTo
    {
        return $this->belongsTo(Asset::class, 'asset_id');
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
    public function assigner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }
}
