<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Models\TenantContactMessage;
use App\Domain\Tenancy\Models\TenantContactThread;
use Illuminate\Support\Collection;

/**
 * Serializes the tenant contact inbox for the page render and AJAX payloads.
 */
class TenantContactInboxPoller
{
    public const FOLDER_ACTIVE = 'active';

    public const FOLDER_ARCHIVED = 'archived';

    /**
     * @return array{
     *     threads: list<array<string, mixed>>,
     *     counts: array{active: int, archived: int},
     *     folder: string,
     *     signature: string
     * }
     */
    public function listThreads(string $folder = self::FOLDER_ACTIVE, string $search = '', int $selectedThreadId = 0): array
    {
        if (! in_array($folder, [self::FOLDER_ACTIVE, self::FOLDER_ARCHIVED], true)) {
            $folder = self::FOLDER_ACTIVE;
        }

        $threadsQuery = TenantContactThread::query()
            ->with(['latestMessage'])
            ->when(
                $folder === self::FOLDER_ARCHIVED,
                fn ($query) => $query->archived(),
                fn ($query) => $query->active(),
            )
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.$search.'%';
                $query->where(function ($inner) use ($like): void {
                    $inner->where('sender_name', 'like', $like)
                        ->orWhere('sender_email', 'like', $like)
                        ->orWhere('subject', 'like', $like);
                });
            })
            ->orderByDesc('last_message_at')
            ->orderByDesc('id');

        /** @var Collection<int, TenantContactThread> $threads */
        $threads = $threadsQuery->limit(100)->get();

        $serialized = $threads
            ->map(fn (TenantContactThread $thread): array => $this->serializeThread($thread, $selectedThreadId))
            ->values()
            ->all();

        $counts = [
            'active' => TenantContactThread::query()->active()->count(),
            'archived' => TenantContactThread::query()->archived()->count(),
        ];

        $signature = md5(json_encode([
            'folder' => $folder,
            'ids' => collect($serialized)->pluck('id')->all(),
            'unread' => collect($serialized)->pluck('unread')->all(),
            'snippets' => collect($serialized)->pluck('snippet')->all(),
            'counts' => $counts,
        ]) ?: '');

        return [
            'threads' => $serialized,
            'counts' => $counts,
            'folder' => $folder,
            'signature' => $signature,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeThread(TenantContactThread $thread, int $selectedThreadId = 0): array
    {
        $latest = $thread->latestMessage;
        $snippet = $latest?->body ?? '';

        if (mb_strlen($snippet) > 90) {
            $snippet = mb_substr($snippet, 0, 87).'…';
        }

        return [
            'id' => $thread->id,
            'display_name' => $thread->sender_name,
            'email' => $thread->sender_email,
            'subject' => $thread->subject,
            'snippet' => $snippet,
            'is_archived' => $thread->isArchived(),
            'unread' => $thread->hasUnreadVisitorMessages(),
            'unread_count' => $thread->unreadVisitorCount(),
            'receipt' => $latest?->receiptStatus() ?? 'pending',
            'avatar_url' => $thread->avatarUrl(),
            'last_message_at' => optional($thread->last_message_at)?->toIso8601String(),
            'is_selected' => $selectedThreadId > 0 && $thread->id === $selectedThreadId,
            'show_url' => route('tenant.contact-messages.show', $thread),
            'reply_url' => route('tenant.contact-messages.reply', $thread),
            'archive_url' => route('tenant.contact-messages.archive', $thread),
            'unarchive_url' => route('tenant.contact-messages.unarchive', $thread),
            'destroy_url' => route('tenant.contact-messages.destroy', $thread),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function serializeMessage(TenantContactMessage $message): array
    {
        return [
            'id' => $message->id,
            'thread_id' => $message->tenant_contact_thread_id,
            'body' => $message->body,
            'sender_name' => $message->sender_name,
            'sender_role' => $message->sender_role,
            'is_staff' => $message->isStaff(),
            'avatar_url' => $message->avatarUrl(),
            'receipt' => $message->receiptStatus(),
            'created_at' => optional($message->created_at)?->toIso8601String(),
        ];
    }
}
