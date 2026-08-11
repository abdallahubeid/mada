<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\AssetCategory;
use App\Domain\Tenancy\Enums\AssetStatus;
use Database\Factories\AssetFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Company asset available for employee custody.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $name
 * @property string $asset_code
 * @property AssetCategory $category
 * @property string|null $serial_number
 * @property Carbon|null $purchase_date
 * @property string|null $purchase_cost
 * @property AssetStatus $status
 * @property string|null $notes
 */
class Asset extends Model
{
    /** @use HasFactory<AssetFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected $table = 'tenant_assets';

    protected static function newFactory(): AssetFactory
    {
        return AssetFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'name',
        'asset_code',
        'category',
        'serial_number',
        'purchase_date',
        'purchase_cost',
        'status',
        'notes',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'category' => 'other',
        'status' => 'available',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'category' => AssetCategory::class,
            'status' => AssetStatus::class,
            'purchase_date' => 'date',
            'purchase_cost' => 'decimal:2',
        ];
    }

    /**
     * @return HasMany<AssetAssignment, $this>
     */
    public function assignments(): HasMany
    {
        return $this->hasMany(AssetAssignment::class, 'asset_id');
    }

    /**
     * @return HasOne<AssetAssignment, $this>
     */
    public function currentAssignment(): HasOne
    {
        return $this->hasOne(AssetAssignment::class, 'asset_id')->ofMany(
            ['assigned_at' => 'max', 'id' => 'max'],
            fn ($query) => $query->whereNull('returned_at'),
        );
    }

    public static function nextAssetCode(): string
    {
        $latest = static::query()
            ->withTrashed()
            ->where('asset_code', 'like', 'AST-%')
            ->orderByDesc('id')
            ->value('asset_code');

        $sequence = 1;

        if (is_string($latest) && preg_match('/^AST-(\d+)$/', $latest, $matches) === 1) {
            $sequence = ((int) $matches[1]) + 1;
        }

        return sprintf('AST-%03d', $sequence);
    }
}
