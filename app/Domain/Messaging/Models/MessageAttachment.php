<?php

namespace App\Domain\Messaging\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A file shared in a conversation.
 *
 * There is deliberately no `url()` accessor. These files live on a private
 * disk and are only reachable through a controller that re-checks conversation
 * membership on every request — exposing a direct URL here would invite
 * callers to render one straight into a Blade view and quietly bypass that
 * check, which is exactly the leak the private disk exists to prevent.
 */
class MessageAttachment extends Model
{
    use BelongsToTenant, HasFactory, SoftDeletes;

    /**
     * @var list<string>
     */
    protected $fillable = [
        'tenant_id',
        'conversation_id',
        'message_id',
        'disk',
        'path',
        'original_name',
        'mime_type',
        'size_bytes',
        'kind',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'size_bytes' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Message, $this>
     */
    public function message(): BelongsTo
    {
        return $this->belongsTo(Message::class);
    }

    /**
     * @return BelongsTo<Conversation, $this>
     */
    public function conversation(): BelongsTo
    {
        return $this->belongsTo(Conversation::class);
    }

    public function isImage(): bool
    {
        return $this->kind === 'image';
    }

    /**
     * Human-readable size for the media drawer.
     */
    public function humanSize(): string
    {
        $bytes = max(0, $this->size_bytes);

        return match (true) {
            $bytes >= 1048576 => round($bytes / 1048576, 1).' م.ب',
            $bytes >= 1024 => round($bytes / 1024).' ك.ب',
            default => $bytes.' بايت',
        };
    }
}
