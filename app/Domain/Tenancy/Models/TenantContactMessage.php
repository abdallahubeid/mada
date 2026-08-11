<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Models\User;
use Database\Factories\TenantContactMessageFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Carbon;

/**
 * A single message inside a tenant contact thread (visitor or staff).
 *
 * Receipts: pending (saved) → delivered (broadcast) → read (opened by staff).
 *
 * @property int $id
 * @property int $tenant_id
 * @property int $tenant_contact_thread_id
 * @property int|null $user_id
 * @property string $sender_role
 * @property string $sender_name
 * @property string $body
 * @property Carbon|null $delivered_at
 * @property Carbon|null $read_at
 * @property Carbon|null $deleted_at
 */
class TenantContactMessage extends Model
{
    /** @use HasFactory<TenantContactMessageFactory> */
    use BelongsToTenant, HasFactory, SoftDeletes;

    public const ROLE_VISITOR = 'visitor';

    public const ROLE_STAFF = 'staff';

    protected $table = 'tenant_contact_messages';

    protected static function newFactory(): TenantContactMessageFactory
    {
        return TenantContactMessageFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'tenant_contact_thread_id',
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
            'deleted_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<TenantContactThread, $this>
     */
    public function thread(): BelongsTo
    {
        return $this->belongsTo(TenantContactThread::class, 'tenant_contact_thread_id');
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function isStaff(): bool
    {
        return $this->sender_role === self::ROLE_STAFF;
    }

    public function isVisitor(): bool
    {
        return $this->sender_role === self::ROLE_VISITOR;
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
