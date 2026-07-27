<?php

namespace App\Models;

use Database\Factories\PlatformNotificationFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Super Admin platform console alert (docs/MODULES.md BR-804).
 *
 * @property int $id
 * @property string $category
 * @property string $title
 * @property string $body
 * @property string|null $target_url
 * @property Carbon|null $read_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
class PlatformNotification extends Model
{
    /** @use HasFactory<PlatformNotificationFactory> */
    use HasFactory, SoftDeletes;

    public const CATEGORY_APPROVAL = 'approval';

    public const CATEGORY_SECURITY = 'security';

    public const CATEGORY_JOB_FAILED = 'job_failed';

    public const CATEGORY_PLAN_LIMIT = 'plan_limit';

    public const CATEGORY_OPS = 'ops';

    /**
     * @var list<string>
     */
    public const CATEGORIES = [
        self::CATEGORY_APPROVAL,
        self::CATEGORY_SECURITY,
        self::CATEGORY_JOB_FAILED,
        self::CATEGORY_PLAN_LIMIT,
        self::CATEGORY_OPS,
    ];

    /**
     * @var list<string>
     */
    protected $fillable = [
        'category',
        'title',
        'body',
        'target_url',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'read_at' => 'datetime',
        ];
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeUnread(Builder $query): Builder
    {
        return $query->whereNull('read_at');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeRead(Builder $query): Builder
    {
        return $query->whereNotNull('read_at');
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeCategory(Builder $query, string $category): Builder
    {
        return $query->where('category', $category);
    }

    public function isRead(): bool
    {
        return $this->read_at !== null;
    }

    public function markAsRead(): void
    {
        if ($this->read_at === null) {
            $this->forceFill(['read_at' => now()])->save();
        }
    }

    public function markAsUnread(): void
    {
        if ($this->read_at !== null) {
            $this->forceFill(['read_at' => null])->save();
        }
    }

    /**
     * Timeframe bucket for the notifications feed UI.
     */
    public function groupKey(): string
    {
        $created = $this->created_at ?? now();

        if ($created->isToday()) {
            return 'today';
        }

        if ($created->greaterThanOrEqualTo(now()->subDays(7))) {
            return 'week';
        }

        return 'older';
    }
}
