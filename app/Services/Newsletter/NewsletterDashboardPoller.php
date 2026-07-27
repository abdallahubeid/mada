<?php

namespace App\Services\Newsletter;

use App\Models\NewsletterSubscriber;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

/**
 * Builds JSON snapshots for the admin newsletter dashboard short-poller.
 */
class NewsletterDashboardPoller
{
    /**
     * @return array{
     *     stats: array{total: int, active: int, unsubscribed: int},
     *     subscribers: list<array<string, mixed>>,
     *     active_subscribers: list<array{id: int, email: string}>,
     *     signature: string,
     *     page: int,
     *     last_page: int
     * }
     */
    public function snapshot(string $status = 'all', string $search = '', int $page = 1): array
    {
        $stats = [
            'total' => NewsletterSubscriber::query()->count(),
            'active' => NewsletterSubscriber::query()->subscribed()->count(),
            'unsubscribed' => NewsletterSubscriber::query()->unsubscribed()->count(),
        ];

        $query = NewsletterSubscriber::query()
            ->orderByDesc('subscribed_at')
            ->orderByDesc('id');

        if ($status === NewsletterSubscriber::STATUS_SUBSCRIBED) {
            $query->subscribed();
        } elseif ($status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            $query->unsubscribed();
        }

        if ($search !== '') {
            $query->where('email', 'like', "%{$search}%");
        }

        /** @var LengthAwarePaginator<int, NewsletterSubscriber> $paginator */
        $paginator = $query->paginate(
            perPage: config('app.paginate_page'),
            page: max(1, $page),
        );

        $subscribers = collect($paginator->items())
            ->values()
            ->map(function (NewsletterSubscriber $subscriber, int $index) use ($paginator): array {
                $row = $this->serializeSubscriber($subscriber);
                $row['index'] = (($paginator->currentPage() - 1) * $paginator->perPage()) + $index + 1;

                return $row;
            })
            ->all();

        $activeSubscribers = NewsletterSubscriber::query()
            ->subscribed()
            ->orderBy('email')
            ->get(['id', 'email'])
            ->map(fn (NewsletterSubscriber $subscriber): array => [
                'id' => $subscriber->id,
                'email' => $subscriber->email,
            ])
            ->values()
            ->all();

        return [
            'stats' => $stats,
            'subscribers' => $subscribers,
            'active_subscribers' => $activeSubscribers,
            'signature' => $this->signature($stats, $subscribers, $activeSubscribers),
            'page' => $paginator->currentPage(),
            'last_page' => $paginator->lastPage(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeSubscriber(NewsletterSubscriber $subscriber): array
    {
        return [
            'id' => $subscriber->id,
            'email' => $subscriber->email,
            'status' => $subscriber->status,
            'status_label' => $subscriber->statusLabel(),
            'is_subscribed' => $subscriber->isSubscribed(),
            'subscribed_at' => optional($subscriber->subscribed_at)?->toIso8601String(),
            'subscribed_at_human' => $subscriber->subscribed_at
                ? $subscriber->subscribed_at->locale('ar')->diffForHumans()
                : '—',
            'toggle_url' => route('admin.newsletter.toggle', $subscriber),
            'destroy_url' => route('admin.newsletter.destroy', $subscriber),
        ];
    }

    /**
     * @param  array{total: int, active: int, unsubscribed: int}  $stats
     * @param  list<array<string, mixed>>  $subscribers
     * @param  list<array{id: int, email: string}>  $activeSubscribers
     */
    public function signature(array $stats, array $subscribers, array $activeSubscribers): string
    {
        $parts = [
            't:'.$stats['total'],
            'a:'.$stats['active'],
            'u:'.$stats['unsubscribed'],
        ];

        foreach ($subscribers as $subscriber) {
            $parts[] = implode(':', [
                $subscriber['id'],
                $subscriber['status'],
                $subscriber['subscribed_at'] ?? '',
                $subscriber['email'],
            ]);
        }

        foreach ($activeSubscribers as $active) {
            $parts[] = 'act:'.$active['id'];
        }

        return hash('xxh3', implode('|', $parts));
    }
}
