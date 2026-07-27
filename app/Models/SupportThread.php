<?php

namespace App\Models;

use Database\Factories\SupportThreadFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * Support inquiry thread (docs/MODULES.md §6, BR-805/BR-806).
 *
 * @property int $id
 * @property int|null $tenant_id
 * @property int|null $user_id
 * @property string $email
 * @property string $name
 * @property string|null $company
 * @property string $subject
 * @property string $status
 * @property Carbon|null $last_message_at
 * @property Carbon|null $deleted_at
 */
class SupportThread extends Model
{
    /** @use HasFactory<SupportThreadFactory> */
    use HasFactory, SoftDeletes;

    public const STATUS_OPEN = 'open';

    public const STATUS_IN_PROGRESS = 'in_progress';

    public const STATUS_RESOLVED = 'resolved';

    public const STATUS_ARCHIVED = 'archived';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'user_id',
        'email',
        'name',
        'company',
        'subject',
        'status',
        'last_message_at',
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
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * @return HasMany<SupportMessage, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(SupportMessage::class)->orderBy('created_at');
    }

    /**
     * @return HasOne<SupportMessage, $this>
     */
    public function latestMessage(): HasOne
    {
        return $this->hasOne(SupportMessage::class)->latestOfMany();
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeActive(Builder $query): Builder
    {
        return $query->whereIn('status', [self::STATUS_OPEN, self::STATUS_IN_PROGRESS]);
    }

    /**
     * @param  Builder<$this>  $query
     * @return Builder<$this>
     */
    public function scopeStatus(Builder $query, string $status): Builder
    {
        return $query->where('status', $status);
    }

    public function hasUnreadCustomerMessages(): bool
    {
        return $this->messages()
            ->where('sender_role', SupportMessage::ROLE_CUSTOMER)
            ->whereNull('read_at')
            ->exists();
    }

    public function displayName(): string
    {
        return filled($this->company) ? (string) $this->company : $this->name;
    }

    public function avatarUrl(): string
    {
        if ($this->relationLoaded('user') && $this->user) {
            return $this->user->avatar_url;
        }

        if ($this->user_id) {
            $user = $this->user()->first();

            if ($user) {
                return $user->avatar_url;
            }
        }

        return User::make(['name' => $this->name])->initialAvatarDataUri();
    }
}
