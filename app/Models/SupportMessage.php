<?php

namespace App\Models;

use Database\Factories\SupportMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A single message inside a support thread.
 *
 * @property int $id
 * @property int $support_thread_id
 * @property int|null $user_id
 * @property string $sender_role
 * @property string $sender_name
 * @property string $body
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 */
class SupportMessage extends Model
{
    /** @use HasFactory<SupportMessageFactory> */
    use HasFactory, SoftDeletes;

    public const ROLE_CUSTOMER = 'customer';

    public const ROLE_ADMIN = 'admin';

    /**
     * @var list<string>
     */
    protected $fillable = [
        'support_thread_id',
        'user_id',
        'sender_role',
        'sender_name',
        'body',
        'delivered_at',
        'read_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'delivered_at' => 'datetime',
            'read_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<SupportThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(SupportThread::class, 'support_thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isAdmin(): bool
    {
        return $this->sender_role === self::ROLE_ADMIN;
    }

    public function isCustomer(): bool
    {
        return $this->sender_role === self::ROLE_CUSTOMER;
    }

    /**
     * WhatsApp-style receipt: pending | delivered | read.
     */
    public function receiptStatus(): string
    {
        if ($this->read_at !== null) {
            return 'read';
        }

        if ($this->delivered_at !== null) {
            return 'delivered';
        }

        return 'pending';
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

        return User::make(['name' => $this->sender_name])->initialAvatarDataUri();
    }
}
