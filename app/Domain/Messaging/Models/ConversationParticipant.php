<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * One person's membership of one thread, and their read watermark.
 *
 * Deliberately NOT soft-deleting: removal from a group has to be a real
 * deletion, because the unique index on (conversation_id, user_id) would
 * otherwise refuse to re-add someone who had previously left. Departure is
 * recorded as a `system` message in the thread instead, which is what the
 * participants can actually see.
 */
class ConversationParticipant extends Model
{
    use BelongsToTenant, HasFactory;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'user_id',
        'role',
        'last_read_message_id',
        'last_read_at',
        'muted_at',
        'joined_at',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'last_read_at' => 'datetime',
            'muted_at' => 'datetime',
            'joined_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Messages in this thread the participant has not yet seen.
     *
     * Own messages are excluded — you have read what you wrote — and the
     * comparison is on `id`, not `created_at`: ids are monotonic and unique,
     * whereas two messages sent in the same second would make a timestamp
     * comparison ambiguous at exactly the boundary the watermark sits on.
     */
    public function unreadCount(): int
    {
        return Message::query()
            ->where('conversation_id', $this->conversation_id)
            ->where('sender_id', '!=', $this->user_id)
            ->when(
                $this->last_read_message_id !== null,
                fn ($query) => $query->where('id', '>', $this->last_read_message_id),
            )
            ->count();
    }
}
