<?php

namespace App\Notifications\Tenant;

use App\Models\User;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Broadcasting\ShouldBroadcastNow;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Notification;

/**
 * Dual-channel (database + Reverb) notification for any tenant user.
 *
 * Recipients are decided by the dispatching side — see {@see TenantNotifier},
 * which fans out to Owners, a role, a permission holder, a specific employee,
 * or an employee's line manager. This class only describes the payload.
 *
 * Broadcast channel is resolved via {@see User::receivesBroadcastNotificationsOn()},
 * which is per-user (`tenant.{tenantId}.notifications.{userId}`), so the same
 * notification works unchanged for an Owner, an HR Manager, or an Employee.
 */
abstract class TenantNotification extends Notification implements ShouldBroadcastNow
{
    use Queueable;

    /**
     * @return list<string>
     */
    public function via(object $notifiable): array
    {
        return ['database', 'broadcast'];
    }

    /**
     * @return array<string, mixed>
     */
    public function toArray(object $notifiable): array
    {
        return [
            'title' => $this->title(),
            'message' => $this->message(),
            'url' => $this->url(),
            'icon' => $this->icon(),
            'severity' => $this->severity(),
            'type' => $this->type(),
        ];
    }

    public function toBroadcast(object $notifiable): BroadcastMessage
    {
        return new BroadcastMessage([
            'id' => $this->id,
            ...$this->toArray($notifiable),
            'unread_count' => $notifiable->unreadNotifications()->count(),
            'sound' => true,
        ]);
    }

    public function broadcastAs(): string
    {
        return 'TenantNotificationCreated';
    }

    abstract protected function title(): string;

    abstract protected function message(): string;

    abstract protected function url(): ?string;

    abstract protected function icon(): string;

    abstract protected function severity(): string;

    abstract protected function type(): string;
}
