<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Messaging\Enums\ConversationType;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A messenger thread — one-to-one or group.
 *
 * Tenant-scoped through {@see BelongsToTenant}, but note that the global scope
 * is NOT what keeps conversations private from colleagues: it only keeps them
 * inside the tenant. Privacy between employees of the SAME tenant comes from
 * participant membership, and every read path must apply
 * {@see self::scopeVisibleTo()} on top of the tenant scope.
 *
 * @property-read Collection<int, ConversationParticipant> $participants
 */
class Conversation extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'type',
        'title',
        'avatar_path',
        'created_by',
        'last_message_at',
        'participants_hash',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => ConversationType::class,
            'last_message_at' => 'datetime',
        ];
    }

    /**
     * The fingerprint that makes a direct thread unique per pair.
     *
     * Sorted before hashing so the pair (7, 3) and the pair (3, 7) produce the
     * same value — otherwise whichever user pressed "message" first would
     * determine the hash and the guarantee would be worthless.
     *
     * Returns null for groups: the same set of people may legitimately have
     * several distinct groups, and the unique index allows repeated nulls.
     *
     * @param  list<int>  $userIds
     */
    public static function hashFor(ConversationType $type, array $userIds): ?string
    {
        if ($type !== ConversationType::Direct) {
            return null;
        }

        $ids = array_values(array_unique(array_map('intval', $userIds)));
        sort($ids);

        return hash('sha256', implode(':', $ids));
    }

    /**
     * Restrict to conversations the given user actually belongs to.
     *
     * This is the privacy boundary, and it is expressed as a membership lookup
     * rather than a permission check on purpose: no role — Owner, HR Manager,
     * or platform Super Admin — grants sight of a thread. There is deliberately
     * no `Gate` involved here, because `Gate::before` in AppServiceProvider
     * grants Owners every ability and would otherwise hand them every
     * conversation in the company.
     *
     * @param  Builder<Conversation>  $query
     */
    public function scopeVisibleTo(Builder $query, User $user): void
    {
        $query->whereHas(
            'participants',
            fn (Builder $participants) => $participants->where('user_id', $user->id),
        );
    }

    /**
     * The caller's inbox: visible, and not shelved by them.
     *
     * Separate from {@see self::scopeVisibleTo()} on purpose. `visibleTo` is
     * the ACCESS boundary and is what `show`/`send` use — a hidden or archived
     * thread is still yours, and opening it by URL or receiving a message in
     * it must keep working. This scope is only about what the sidebar lists.
     *
     * @param  Builder<Conversation>  $query
     */
    public function scopeInboxFor(Builder $query, User $user, bool $archived = false): void
    {
        $query->whereHas('participants', function (Builder $participants) use ($user, $archived): void {
            $participants->where('user_id', $user->id)
                ->whereNull('hidden_at')
                ->when($archived,
                    fn (Builder $q) => $q->whereNotNull('archived_at'),
                    fn (Builder $q) => $q->whereNull('archived_at'),
                );
        });
    }

    /**
     * @return HasMany<ConversationParticipant, $this>
     */
    public function participants(): HasMany
    {
        return $this->hasMany(ConversationParticipant::class);
    }

    /**
     * @return HasMany<Message, $this>
     */
    public function messages(): HasMany
    {
        return $this->hasMany(Message::class);
    }

    /**
     * @return HasMany<MessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function isGroup(): bool
    {
        return $this->type === ConversationType::Group;
    }

    /**
     * Whether the given user may read this thread.
     *
     * Queries the relation rather than trusting an already-loaded collection,
     * because callers reach this from a channel authorizer where nothing is
     * eager-loaded and a stale in-memory relation would be a security answer
     * derived from cached data.
     */
    public function includes(User $user): bool
    {
        return $this->participants()->where('user_id', $user->id)->exists();
    }

    /**
     * The thread title as the given user should see it.
     *
     * A direct thread has no stored title — it is named after the OTHER
     * person, which differs per viewer, so it cannot be a column.
     */
    public function displayTitleFor(User $user): string
    {
        if ($this->isGroup()) {
            return $this->title ?? 'مجموعة بلا اسم';
        }

        $other = $this->participants
            ->firstWhere(fn (ConversationParticipant $p): bool => $p->user_id !== $user->id);

        return $other?->user?->name ?? 'محادثة';
    }
}
