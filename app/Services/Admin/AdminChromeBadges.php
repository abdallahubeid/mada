<?php

namespace App\Services\Admin;

use App\Models\SupportMessage;
use App\Models\SupportThread;

/**
 * TopBar unread badge counts for the platform console chrome.
 */
class AdminChromeBadges
{
    public function __construct(private PlatformNotifications $notifications) {}

    /**
     * Distinct support threads that still have unread customer messages.
     */
    public function unreadMessagesCount(): int
    {
        return SupportThread::query()
            ->whereHas('messages', function ($query): void {
                $query
                    ->where('sender_role', SupportMessage::ROLE_CUSTOMER)
                    ->whereNull('read_at');
            })
            ->count();
    }

    public function unreadNotificationsCount(): int
    {
        return $this->notifications->unreadCount();
    }

    /**
     * @return array{messages_unread: int, notifications_unread: int, signature: string}
     */
    public function snapshot(): array
    {
        $messages = $this->unreadMessagesCount();
        $notifications = $this->unreadNotificationsCount();

        return [
            'messages_unread' => $messages,
            'notifications_unread' => $notifications,
            'signature' => hash('xxh3', "{$messages}|{$notifications}"),
        ];
    }
}
