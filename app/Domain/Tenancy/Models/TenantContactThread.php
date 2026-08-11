<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Models\User;
use Database\Factories\TenantContactThreadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Public portal contact conversation, threaded by sender_email per tenant.
 *
 * @property int $id
 * @property int $tenant_id
 * @property string $sender_name
 * @property string $sender_email
 * @property string $subject
 * @property string $status
 * @property Carbon|null $last_message_at
 * @property Carbon|null $deleted_at
 */
class TenantContactThread extends Model
{
    /** @use HasFactory<TenantContactThreadFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_ARCHIVED = 'archived';

    protected $table = 'tenant_contact_threads';

    protected static function newFactory(): TenantContactThreadFactory
    {
        return TenantContactThreadFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'sender_name',
        'sender_email',
        'subject',
        'status',
        'last_message_at',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => self::STATUS_OPEN,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_message_at' => 'datetime',
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return HasMany<TenantContactMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(TenantContactMessage::class)->orderBy('created_at');
    }

    /**
     * @return HasOne<TenantContactMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(TenantContactMessage::class)->latestOfMany();
    }

    /**
     * Active (non-archived) conversations shown in the inbox list.
     *
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->where('status', '!=', self::STATUS_ARCHIVED);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeArchived(Builder $query): Builder
    {
        return $query->where('status', self::STATUS_ARCHIVED);
    }

    public function hasUnreadVisitorMessages(): bool
    {
        return $this->messages()
            ->where('sender_role', TenantContactMessage::ROLE_VISITOR)
            ->whereNull('read_at')
            ->exists();
    }

    public function unreadVisitorCount(): int
    {
        return $this->messages()
            ->where('sender_role', TenantContactMessage::ROLE_VISITOR)
            ->whereNull('read_at')
            ->count();
    }

    public function avatarUrl(): string
    {
        return User::make(['name' => $this->sender_name])->initialAvatarDataUri();
    }

    public function isArchived(): bool
    {
        return $this->status === self::STATUS_ARCHIVED;
    }
}
