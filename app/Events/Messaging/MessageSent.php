<?php

namespace App\Events\Messaging;

use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\MessageAttachment;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * A message was posted to a conversation.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ShouldBroadcastNow, NOT ShouldBroadcast
 *
 * `QUEUE_CONNECTION=database`, so a queued broadcast waits on a worker that
 * polls MySQL — typically one to three seconds, and worse under load, which is
 * exactly when people are chatting. That is acceptable for a notification bell
 * and unusable for a chat bubble.
 *
 * Broadcasting inline costs this request the Reverb round trip (single-digit
 * milliseconds on a local socket) and is the agreed trade for sub-100ms
 * delivery without adding Redis.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * THE PAYLOAD IS BUILT BY HAND
 *
 * `broadcastWith()` names every field explicitly instead of serialising the
 * model. Broadcasting bypasses Eloquent: the global tenant scope protects
 * queries, not payloads, so a serialised model would happily ship whatever
 * columns and loaded relations it happened to be carrying — including a
 * `sender` User with `tenant_id`, `email` and `remember_token` on it.
 *
 * The body is included because the client renders it directly; nothing else
 * about the sender travels beyond the name and id needed to draw the bubble.
 * ─────────────────────────────────────────────────────────────────────────
 */
class MessageSent implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(public Message $message) {}

    /**
     * @return list<PrivateChannel>
     */
    public function broadcastOn(): array
    {
        /*
         * Tenant id is in the channel name, not only the conversation id.
         * Conversation ids are globally sequential, so a name without the
         * tenant segment would be guessable across tenants and would put the
         * entire isolation guarantee on the authorizer alone. With the tenant
         * in the name, a subscribe attempt from the wrong tenant cannot even
         * describe the right channel.
         */
        return [
            new PrivateChannel(
                "tenant.{$this->message->tenant_id}.conversations.{$this->message->conversation_id}"
            ),
        ];
    }

    public function broadcastAs(): string
    {
        return 'MessageSent';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $message = $this->message;

        return [
            'id' => $message->id,
            'conversation_id' => $message->conversation_id,
            'sender_id' => $message->sender_id,
            'sender_name' => $message->sender?->name ?? 'مستخدم محذوف',
            'type' => $message->type->value,
            'body' => $message->body,
            'parent_id' => $message->parent_id,
            'sent_at' => $message->sent_at?->toIso8601String(),
            /*
             * Attachment METADATA only — id, label, size and the two route
             * URLs. Never `path`, never `disk`.
             *
             * The URLs are safe to broadcast because they are not credentials:
             * both endpoints re-check conversation membership when they are
             * requested, so a payload that somehow reached the wrong subscriber
             * would still yield 404s. Shipping the path instead would hand out
             * the one piece of information the private disk is meant to hide.
             */
            'attachments' => $message->attachments->map(fn (MessageAttachment $attachment): array => [
                'id' => $attachment->id,
                'kind' => $attachment->kind,
                'name' => $attachment->original_name,
                'size' => $attachment->humanSize(),
                'preview_url' => $attachment->isImage()
                    ? route('tenant.messenger.attachments.preview', $attachment->id)
                    : null,
                'download_url' => route('tenant.messenger.attachments.download', $attachment->id),
            ])->all(),
        ];
    }
}
