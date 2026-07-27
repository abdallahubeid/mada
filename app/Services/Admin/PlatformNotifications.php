<?php

namespace App\Services\Admin;

use App\Models\PlatformNotification;
use Illuminate\Support\Collection;

/**
 * Reads platform notifications for the console feed and TopBar badges.
 */
class PlatformNotifications
{
    /**
     * @return list<array{id: int, category: string, title: string, body: string, target: string|null, target_url: string|null, time: string, read: bool, group: string}>
     */
    public function all(?string $category = null): array
    {
        $query = PlatformNotification::query()->latest('created_at');

        if ($category !== null && $category !== 'all') {
            $query->category($category);
        }

        return $query
            ->get()
            ->map(fn (PlatformNotification $notification): array => $this->serialize($notification))
            ->values()
            ->all();
    }

    public function unreadCount(): int
    {
        return PlatformNotification::query()->unread()->count();
    }

    /**
     * @return array<string, int>
     */
    public function unreadCountsByCategory(): array
    {
        /** @var Collection<string, int> $grouped */
        $grouped = PlatformNotification::query()
            ->unread()
            ->selectRaw('category, COUNT(*) as aggregate')
            ->groupBy('category')
            ->pluck('aggregate', 'category');

        $counts = ['all' => (int) $grouped->sum()];

        foreach (PlatformNotification::CATEGORIES as $category) {
            $counts[$category] = (int) ($grouped[$category] ?? 0);
        }

        return $counts;
    }

    public function markAllAsRead(): int
    {
        return PlatformNotification::query()
            ->unread()
            ->update(['read_at' => now()]);
    }

    public function destroyAll(): int
    {
        return PlatformNotification::query()->delete();
    }

    /**
     * @return array{id: int, category: string, title: string, body: string, target: string|null, target_url: string|null, time: string, read: bool, group: string}
     */
    public function serialize(PlatformNotification $notification): array
    {
        return [
            'id' => $notification->id,
            'category' => $notification->category,
            'title' => $notification->title,
            'body' => $notification->body,
            'target' => null,
            'target_url' => $notification->target_url,
            'time' => optional($notification->created_at)?->locale('ar')->diffForHumans() ?? '',
            'read' => $notification->isRead(),
            'group' => $notification->groupKey(),
        ];
    }
}
