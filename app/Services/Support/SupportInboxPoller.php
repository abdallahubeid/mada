<?php

namespace App\Services\Support;

use App\Models\SupportMessage;
use App\Models\SupportThread;
use Illuminate\Support\Collection;

/**
 * Builds JSON snapshots for the admin messages inbox short-poller.
 */
class SupportInboxPoller
{
    /**
     * @var array<string, array{label: string, badge: string, dot: string}>
     */
    public const STATUS_META = [
        SupportThread::STATUS_OPEN => [
            'label' => 'مفتوح',
            'badge' => 'bg-amber-500/10 text-amber-600 dark:text-amber-400',
            'dot' => 'bg-amber-500',
        ],
        SupportThread::STATUS_IN_PROGRESS => [
            'label' => 'قيد المعالجة',
            'badge' => 'bg-sky-500/10 text-sky-600 dark:text-sky-400',
            'dot' => 'bg-sky-500',
        ],
        SupportThread::STATUS_RESOLVED => [
            'label' => 'تم الحل',
            'badge' => 'bg-emerald-500/10 text-emerald-600 dark:text-emerald-400',
            'dot' => 'bg-emerald-400',
        ],
        SupportThread::STATUS_ARCHIVED => [
            'label' => 'مؤرشف',
            'badge' => 'bg-mist-100 text-mist-600 dark:bg-ink-700 dark:text-mist-300',
            'dot' => 'bg-mist-400',
        ],
    ];

    public function __construct(private SupportInbox $inbox) {}

    /**
     * @return array{
     *     counts: array<string, int>,
     *     threads: list<array<string, mixed>>,
     *     messages: list<array<string, mixed>>,
     *     selected_exists: bool,
     *     signature: string
     * }
     */
    public function snapshot(string $activeStatus, string $search = '', int $selectedThreadId = 0, int $afterMessageId = 0): array
    {
        $list = $this->listThreads($activeStatus, $search, $selectedThreadId);
        $messagesPayload = $this->messagesSince($selectedThreadId, $afterMessageId);

        return [
            'counts' => $list['counts'],
            'threads' => $list['threads'],
            'signature' => $list['signature'],
            'messages' => $messagesPayload['messages'],
            'selected_exists' => $messagesPayload['selected_exists'],
        ];
    }

    /**
     * @return array{
     *     counts: array<string, int>,
     *     threads: list<array<string, mixed>>,
     *     signature: string
     * }
     */
    public function listThreads(string $activeStatus, string $search = '', int $selectedThreadId = 0): array
    {
        $counts = [];

        foreach (array_keys(self::STATUS_META) as $status) {
            $counts[$status] = SupportThread::query()->status($status)->count();
        }

        $threadsQuery = SupportThread::query()
            ->status($activeStatus)
            ->with(['user.avatar', 'latestMessage'])
            ->withCount([
                'messages as unread_customer_count' => fn ($query) => $query
                    ->where('sender_role', SupportMessage::ROLE_CUSTOMER)
                    ->whereNull('read_at'),
            ])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        if ($search !== '') {
            $threadsQuery->where(function ($query) use ($search): void {
                $query->where('name', 'like', "%{$search}%")
                    ->orWhere('email', 'like', "%{$search}%")
                    ->orWhere('company', 'like', "%{$search}%")
                    ->orWhere('subject', 'like', "%{$search}%");
            });
        }

        /** @var Collection<int, SupportThread> $threads */
        $threads = $threadsQuery->get();

        $threadPayload = $threads->map(
            fn (SupportThread $thread): array => $this->serializeThread($thread, $activeStatus, $search, $selectedThreadId)
        )->values()->all();

        return [
            'counts' => $counts,
            'threads' => $threadPayload,
            'signature' => $this->signature($threadPayload, $counts),
        ];
    }

    /**
     * @return array{messages: list<array<string, mixed>>, selected_exists: bool}
     */
    public function messagesSince(int $selectedThreadId, int $afterMessageId = 0): array
    {
        if ($selectedThreadId <= 0) {
            return [
                'messages' => [],
                'selected_exists' => true,
            ];
        }

        $selected = SupportThread::query()
            ->with(['user.avatar'])
            ->find($selectedThreadId);

        if ($selected === null) {
            return [
                'messages' => [],
                'selected_exists' => false,
            ];
        }

        $this->inbox->markCustomerMessagesAsRead($selected);

        $messagesQuery = $selected->messages()
            ->with('user.avatar')
            ->orderBy('id');

        if ($afterMessageId > 0) {
            $messagesQuery->where('id', '>', $afterMessageId);
        }

        $messages = $messagesQuery->get()
            ->map(fn (SupportMessage $message): array => $this->serializeMessage($message))
            ->values()
            ->all();

        return [
            'messages' => $messages,
            'selected_exists' => true,
        ];
    }

    /**
     * @param  list<array<string, mixed>>  $threads
     * @param  array<string, int>  $counts
     */
    public function signature(array $threads, array $counts): string
    {
        $parts = [];

        foreach ($counts as $status => $count) {
            $parts[] = $status.':'.$count;
        }

        foreach ($threads as $thread) {
            $parts[] = implode(':', [
                $thread['id'],
                $thread['last_message_at'] ?? '',
                $thread['snippet'] ?? '',
                ($thread['unread'] ?? false) ? '1' : '0',
                $thread['status'],
            ]);
        }

        return hash('xxh3', implode('|', $parts));
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeThread(SupportThread $thread, string $activeStatus, string $search, int $selectedThreadId): array
    {
        $meta = self::STATUS_META[$thread->status] ?? self::STATUS_META[SupportThread::STATUS_OPEN];
        $isSelected = $selectedThreadId === $thread->id;
        $unread = ! $isSelected && ($thread->unread_customer_count ?? 0) > 0;

        return [
            'id' => $thread->id,
            'display_name' => $thread->displayName(),
            'subject' => $thread->subject,
            'snippet' => $thread->latestMessage?->body ?? '',
            'status' => $thread->status,
            'status_label' => $meta['label'],
            'status_badge' => $meta['badge'],
            'status_dot' => $meta['dot'],
            'avatar_url' => $thread->avatarUrl(),
            'last_message_at' => optional($thread->last_message_at)->toIso8601String(),
            'unread' => $unread,
            'is_selected' => $isSelected,
            'can_archive' => $thread->status !== SupportThread::STATUS_ARCHIVED,
            'open_url' => route('admin.messages', [
                'status' => $activeStatus,
                'thread' => $thread->id,
                'q' => $search !== '' ? $search : null,
            ]),
            'archive_url' => route('admin.messages.archive', $thread),
            'destroy_url' => route('admin.messages.destroy', $thread),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(SupportMessage $message): array
    {
        return [
            'id' => $message->id,
            'body' => $message->body,
            'sender_name' => $message->sender_name,
            'is_admin' => $message->isAdmin(),
            'avatar_url' => $message->avatarUrl(),
            'created_at' => optional($message->created_at)->toIso8601String(),
            'receipt' => $message->receiptStatus(),
        ];
    }
}
