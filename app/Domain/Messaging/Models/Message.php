<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Messaging\Enums\MessageType;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Message extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'sender_id',
        'type',
        'body',
        'parent_id',
        'metadata',
        'sent_at',
        'pinned_at',
        'pinned_by',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'type' => MessageType::class,
            'metadata' => 'array',
            'sent_at' => 'datetime',
            'pinned_at' => 'datetime',
        ];
    }

    /**
     * A page of scrollback, oldest-to-newest, addressed by keyset.
     *
     * `$before` is the lowest id already on screen. Paging this way rather
     * than with OFFSET keeps every page a single index seek no matter how deep
     * the thread goes, and — unlike OFFSET — it cannot skip or duplicate a
     * message when new ones arrive mid-scroll, which on a live chat is not an
     * edge case but the normal condition.
     *
     * @param  Builder<Message>  $query
     */
    public function scopePage(Builder $query, int $conversationId, ?int $before = null, int $limit = 30): void
    {
        $query->where('conversation_id', $conversationId)
            ->when($before !== null, fn (Builder $q) => $q->where('id', '<', $before))
            ->orderByDesc('id')
            ->limit($limit);
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
    public function sender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'sender_id');
    }

    /**
     * The message being replied to, if any.
     *
     * @return BelongsTo<Message, $this>
     */
    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    /**
     * @return HasMany<MessageReaction, $this>
     */
    public function reactions(): HasMany
    {
        return $this->hasMany(MessageReaction::class);
    }

    /**
     * @return HasMany<MessageAttachment, $this>
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(MessageAttachment::class);
    }

    public function isSystem(): bool
    {
        return $this->type === MessageType::System;
    }

    /**
     * Whether the body is a single emoji and nothing else.
     *
     * Rendered oversized when true — the convention every messenger follows,
     * where a lone emoji is the message rather than decoration on one.
     * Deliberately narrow: `mb_strlen` of 1–2 covers a bare emoji and a
     * variation-selector pair, and anything longer falls back to normal text
     * rather than trying to parse grapheme clusters in a view helper.
     */
    public function isLoneEmoji(): bool
    {
        $body = trim((string) $this->body);

        if ($body === '' || mb_strlen($body) > 2) {
            return false;
        }

        return preg_match('/^[\p{So}\p{Sk}\x{FE0F}\x{200D}]+$/u', $body) === 1;
    }
}
