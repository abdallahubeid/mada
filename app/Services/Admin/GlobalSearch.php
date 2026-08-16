<?php

namespace App\Services\Admin;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Models\SupportThread;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Collection;

/**
 * Dual-mode platform console search: navigation pages + entity records.
 */
class GlobalSearch
{
    public const MIN_QUERY_LENGTH = 2;

    public const SUGGEST_LIMIT = 5;

    public const RESULTS_LIMIT = 25;

    public function __construct(private AdminNavigationCatalog $navigation) {}

    /**
     * @return array{
     *     query: string,
     *     context: string|null,
     *     total: int,
     *     groups: list<array{key: string, label: string, items: list<array<string, mixed>>}>
     * }
     */
    public function search(string $query, ?int $perGroup = null, ?string $context = null): array
    {
        $query = trim($query);
        $context = $this->normalizeContext($context);
        $limit = $perGroup ?? self::RESULTS_LIMIT;

        if (mb_strlen($query) < self::MIN_QUERY_LENGTH) {
            return [
                'query' => $query,
                'context' => $context,
                'total' => 0,
                'groups' => [],
            ];
        }

        $groups = [
            $this->navigationGroup($query, max($limit, 8)),
            $this->tenantsGroup($query, $limit),
            $this->messagesGroup($query, $limit),
            $this->newsletterGroup($query, $limit),
            $this->campaignsGroup($query, $limit),
        ];

        $groups = array_values(array_filter(
            $groups,
            fn (array $group): bool => count($group['items']) > 0
        ));

        $groups = $this->prioritizeByContext($groups, $context);

        return [
            'query' => $query,
            'context' => $context,
            'total' => array_sum(array_map(fn (array $g): int => count($g['items']), $groups)),
            'groups' => $groups,
        ];
    }

    /**
     * @return array{
     *     query: string,
     *     context: string|null,
     *     total: int,
     *     groups: list<array{key: string, label: string, items: list<array<string, mixed>>}>
     * }
     */
    public function suggest(string $query, ?string $context = null): array
    {
        return $this->search($query, self::SUGGEST_LIMIT, $context);
    }

    /**
     * @return array{key: string, label: string, items: list<array<string, mixed>>}
     */
    private function navigationGroup(string $query, int $limit): array
    {
        return [
            'key' => 'navigation',
            'label' => 'صفحات النظام',
            'items' => $this->navigation->match($query, $limit),
        ];
    }

    /**
     * @return array{key: string, label: string, items: list<array<string, mixed>>}
     */
    private function tenantsGroup(string $query, int $limit): array
    {
        /** @var Collection<int, Tenant> $tenants */
        $tenants = Tenant::query()
            ->where(function (Builder $builder) use ($query): void {
                $this->whereLike($builder, 'name', $query);
                $this->orWhereLike($builder, 'slug', $query);
                $this->orWhereLike($builder, 'industry', $query);
            })
            ->orderBy('name')
            ->limit($limit)
            ->get();

        return [
            'key' => 'tenants',
            'label' => 'المستأجرون',
            'items' => $tenants->map(fn (Tenant $tenant): array => [
                'title' => $tenant->name,
                'subtitle' => $tenant->slug,
                'url' => route('admin.tenants.show', $tenant),
                'scope' => 'tenants',
                'anchor' => 'mada-search-tenant-'.$tenant->id,
                'mode' => 'scroll',
            ])->values()->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, items: list<array<string, mixed>>}
     */
    private function messagesGroup(string $query, int $limit): array
    {
        /** @var Collection<int, SupportThread> $threads */
        $threads = SupportThread::query()
            ->where(function (Builder $builder) use ($query): void {
                $this->whereLike($builder, 'name', $query);
                $this->orWhereLike($builder, 'email', $query);
                $this->orWhereLike($builder, 'company', $query);
                $this->orWhereLike($builder, 'subject', $query);
            })
            ->orderByDesc('last_message_at')
            ->limit($limit)
            ->get();

        return [
            'key' => 'messages',
            'label' => 'الرسائل والدعم',
            'items' => $threads->map(function (SupportThread $thread): array {
                $anchor = 'mada-search-thread-'.$thread->id;

                return [
                    'title' => $thread->subject,
                    'subtitle' => trim(($thread->company ?: $thread->name).' · '.$thread->email),
                    'url' => route('admin.messages', [
                        'status' => $thread->status,
                        'thread' => $thread->id,
                        'highlight' => 'thread-'.$thread->id,
                    ]),
                    'scope' => 'messages',
                    'anchor' => $anchor,
                    'mode' => 'scroll',
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, items: list<array<string, mixed>>}
     */
    private function newsletterGroup(string $query, int $limit): array
    {
        /** @var Collection<int, NewsletterSubscriber> $subscribers */
        $subscribers = NewsletterSubscriber::query()
            ->where(function (Builder $builder) use ($query): void {
                $this->whereLike($builder, 'email', $query);
                $this->orWhereLike($builder, 'status', $query);
            })
            ->orderByDesc('subscribed_at')
            ->limit($limit)
            ->get();

        return [
            'key' => 'newsletter',
            'label' => 'مشتركو النشرة',
            'items' => $subscribers->map(function (NewsletterSubscriber $subscriber): array {
                $anchor = 'mada-search-subscriber-'.$subscriber->id;

                return [
                    'title' => $subscriber->email,
                    'subtitle' => $subscriber->status === NewsletterSubscriber::STATUS_SUBSCRIBED
                        ? 'مشترك نشط'
                        : 'ملغى الاشتراك',
                    'url' => route('admin.newsletter.index', [
                        'q' => $subscriber->email,
                        'highlight' => 'subscriber-'.$subscriber->id,
                    ]),
                    'scope' => 'newsletter',
                    'anchor' => $anchor,
                    'mode' => 'scroll',
                ];
            })->values()->all(),
        ];
    }

    /**
     * @return array{key: string, label: string, items: list<array<string, mixed>>}
     */
    private function campaignsGroup(string $query, int $limit): array
    {
        /** @var Collection<int, NewsletterCampaign> $campaigns */
        $campaigns = NewsletterCampaign::query()
            ->where(function (Builder $builder) use ($query): void {
                $this->whereLike($builder, 'subject', $query);
                $this->orWhereLike($builder, 'content', $query);
            })
            ->orderByDesc('sent_at')
            ->limit($limit)
            ->get();

        return [
            'key' => 'newsletter_campaigns',
            'label' => 'الحملات البريدية',
            'items' => $campaigns->map(function (NewsletterCampaign $campaign): array {
                $anchor = 'mada-search-campaign-'.$campaign->id;

                return [
                    'title' => $campaign->subject,
                    'subtitle' => $campaign->recipients_count.' مستلم',
                    'url' => route('admin.newsletter.campaigns.index', [
                        'highlight' => 'campaign-'.$campaign->id,
                    ]),
                    'scope' => 'newsletter_campaigns',
                    'anchor' => $anchor,
                    'mode' => 'scroll',
                ];
            })->values()->all(),
        ];
    }

    /**
     * @param  list<array{key: string, label: string, items: list<array<string, mixed>>}>  $groups
     * @return list<array{key: string, label: string, items: list<array<string, mixed>>}>
     */
    private function prioritizeByContext(array $groups, ?string $context): array
    {
        if ($context === null || $groups === []) {
            return $groups;
        }

        $navigation = [];
        $preferred = [];
        $rest = [];

        foreach ($groups as $group) {
            if ($group['key'] === 'navigation') {
                $navigation[] = $group;
            } elseif ($group['key'] === $context) {
                $preferred[] = $group;
            } else {
                $rest[] = $group;
            }
        }

        return [...$navigation, ...$preferred, ...$rest];
    }

    private function normalizeContext(?string $context): ?string
    {
        $context = is_string($context) ? trim($context) : null;

        $allowed = [
            'dashboard',
            'tenants',
            'plans',
            'faqs',
            'landing',
            'notifications',
            'messages',
            'newsletter',
            'newsletter_campaigns',
            'audit',
            'account',
            'admins',
            'search',
        ];

        if ($context === null || $context === '' || ! in_array($context, $allowed, true)) {
            return null;
        }

        return $context;
    }

    private function whereLike(Builder $builder, string $column, string $needle): void
    {
        $builder->whereRaw('LOWER('.$column.') LIKE ?', ['%'.$this->likeNeedle($needle).'%']);
    }

    private function orWhereLike(Builder $builder, string $column, string $needle): void
    {
        $builder->orWhereRaw('LOWER('.$column.') LIKE ?', ['%'.$this->likeNeedle($needle).'%']);
    }

    private function likeNeedle(string $needle): string
    {
        return mb_strtolower(trim($needle));
    }
}
