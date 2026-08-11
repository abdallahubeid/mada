<?php

namespace App\Events\Tenant;

use App\Domain\Tenancy\Models\TenantContactMessage;
use App\Domain\Tenancy\Models\TenantContactThread;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Live inbox event for tenant contact chat (Reverb).
 *
 * Broadcasts on each Owner's `tenant.{tenantId}.notifications.{userId}` channel.
 */
class NewContactMessageReceived implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    /**
     * @param  list<int|string>  $ownerIds
     */
    public function __construct(
        public int $tenantId,
        public TenantContactThread $thread,
        public TenantContactMessage $message,
        public array $ownerIds = [],
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return collect($this->ownerIds)
            ->filter()
            ->unique()
            ->values()
            ->map(fn (int|string $userId): PrivateChannel => new PrivateChannel(
                'tenant.'.$this->tenantId.'.notifications.'.$userId
            ))
            ->all();
    }

    public function broadcastAs(): string
    {
        return 'NewContactMessageReceived';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        $snippet = mb_strlen($this->message->body) > 120
            ? mb_substr($this->message->body, 0, 117).'…'
            : $this->message->body;

        return [
            'thread_id' => $this->thread->id,
            'message_id' => $this->message->id,
            'sender_name' => $this->thread->sender_name,
            'sender_email' => $this->thread->sender_email,
            'subject' => $this->thread->subject,
            'snippet' => $snippet,
            'body' => $this->message->body,
            'sender_role' => $this->message->sender_role,
            'receipt' => $this->message->receiptStatus(),
            'avatar_url' => $this->thread->avatarUrl(),
            'unread' => true,
            'created_at' => optional($this->message->created_at)?->toIso8601String(),
            'last_message_at' => optional($this->thread->last_message_at ?? $this->message->created_at)?->toIso8601String(),
            'show_url' => route('tenant.contact-messages.show', $this->thread),
            'archive_url' => route('tenant.contact-messages.archive', $this->thread),
            'destroy_url' => route('tenant.contact-messages.destroy', $this->thread),
            'inbox_url' => route('tenant.contact-messages.index'),
        ];
    }
}
