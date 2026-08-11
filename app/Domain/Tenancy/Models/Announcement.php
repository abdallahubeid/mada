<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\AnnouncementType;
use App\Models\User;
use Database\Factories\AnnouncementFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Tenant company announcement / broadcast.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $title
 * @property string $content
 * @property AnnouncementType $type
 * @property Carbon|null $published_at
 * @property Carbon|null $expires_at
 * @property bool $is_pinned
 * @property int|null $created_by
 */
class Announcement extends Model
{
    /** @use HasFactory<AnnouncementFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    protected static function newFactory(): AnnouncementFactory
    {
        return AnnouncementFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'title',
        'content',
        'type',
        'published_at',
        'expires_at',
        'is_pinned',
        'created_by',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'type' => 'info',
        'is_pinned' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => AnnouncementType::class,
            'published_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_pinned' => 'boolean',
        ];
    }

    /**
     * @param  Builder<static>  $query
     * @return Builder<static>
     */
    public function scopeActive(Builder $query): Builder
    {
        $now = now();

        return $query
            ->where(function (Builder $inner) use ($now): void {
                $inner->whereNull('published_at')
                    ->orWhere('published_at', '<=', $now);
            })
            ->where(function (Builder $inner) use ($now): void {
                $inner->whereNull('expires_at')
                    ->orWhere('expires_at', '>', $now);
            });
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function isActive(): bool
    {
        $published = $this->published_at === null || $this->published_at->lte(now());

        return $published && ! $this->isExpired();
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
