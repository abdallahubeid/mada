<?php

namespace App\Events\Platform;

use App\Models\PlatformNotification;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Broadcast after an urgent platform notification is persisted.
 */
class PlatformNotificationCreated implements ShouldBroadcastNow
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public PlatformNotification $notification,
        public int $unreadCount,
    ) {}

    /**
     * @return array<int, PrivateChannel>
     */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel('admin.notifications'),
        ];
    }

    public function broadcastAs(): string
    {
        return 'PlatformNotificationCreated';
    }

    /**
     * @return array<string, mixed>
     */
    public function broadcastWith(): array
    {
        return [
            'id' => $this->notification->id,
            'category' => $this->notification->category,
            'title' => $this->notification->title,
            'body' => $this->notification->body,
            'target_url' => $this->notification->target_url,
            'created_at' => optional($this->notification->created_at)?->toIso8601String(),
            'unread_count' => $this->unreadCount,
        ];
    }
}
