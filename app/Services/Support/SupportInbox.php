<?php

namespace App\Services\Support;

use App\Http\Requests\Marketing\ContactRequest;
use App\Models\SupportMessage;
use App\Models\SupportThread;
use App\Models\User;
use App\Services\Admin\PlatformNotificationPublisher;
use Illuminate\Support\Facades\DB;

/**
 * Creates / appends support threads from public contact and admin replies.
 */
class SupportInbox
{
    public function __construct(private PlatformNotificationPublisher $notifications) {}

    /**
     * Find an active thread for the email or create a new open thread, then append the message.
     *
     * @param  array{name: string, email: string, company?: string|null, subject: string, message: string}  $inquiry
     */
    public function ingestContactInquiry(array $inquiry, ?User $user = null): SupportThread
    {
        $thread = DB::transaction(function () use ($inquiry, $user): SupportThread {
            $email = strtolower(trim($inquiry['email']));

            $thread = SupportThread::query()
                ->active()
                ->whereRaw('LOWER(email) = ?', [$email])
                ->latest('last_message_at')
                ->lockForUpdate()
                ->first();

            $subjectLabel = ContactRequest::SUBJECTS[$inquiry['subject']] ?? $inquiry['subject'];

            if ($thread === null) {
                $thread = SupportThread::query()->create([
                    'tenant_id' => $user?->tenant_id,
                    'user_id' => $user?->id,
                    'email' => $email,
                    'name' => $inquiry['name'],
                    'company' => $inquiry['company'] ?? null,
                    'subject' => $subjectLabel,
                    'status' => SupportThread::STATUS_OPEN,
                    'last_message_at' => now(),
                ]);
            } else {
                $thread->fill([
                    'name' => $inquiry['name'],
                    'company' => $inquiry['company'] ?? $thread->company,
                    'user_id' => $thread->user_id ?? $user?->id,
                    'tenant_id' => $thread->tenant_id ?? $user?->tenant_id,
                    'last_message_at' => now(),
                ])->save();
            }

            $this->appendMessage($thread, [
                'user_id' => $user?->id,
                'sender_role' => SupportMessage::ROLE_CUSTOMER,
                'sender_name' => $inquiry['name'],
                'body' => $inquiry['message'],
            ], broadcast: false);

            return $thread->fresh(['messages', 'user']) ?? $thread;
        });

        $this->notifications->newSupportMessage($thread);

        return $thread;
    }

    /**
     * @param  array{user_id?: int|null, sender_role: string, sender_name: string, body: string}  $payload
     */
    public function appendMessage(SupportThread $thread, array $payload, bool $broadcast = true): SupportMessage
    {
        $message = $thread->messages()->create([
            'user_id' => $payload['user_id'] ?? null,
            'sender_role' => $payload['sender_role'],
            'sender_name' => $payload['sender_name'],
            'body' => $payload['body'],
            'delivered_at' => now(),
            'read_at' => null,
        ]);

        $thread->forceFill(['last_message_at' => now()])->save();

        if ($broadcast && ($payload['sender_role'] ?? null) === SupportMessage::ROLE_CUSTOMER) {
            $this->notifications->newSupportMessage($thread->fresh() ?? $thread);
        }

        return $message;
    }

    public function replyAsAdmin(SupportThread $thread, User $admin, string $body): SupportMessage
    {
        return DB::transaction(function () use ($thread, $admin, $body): SupportMessage {
            if ($thread->status === SupportThread::STATUS_OPEN) {
                $thread->update(['status' => SupportThread::STATUS_IN_PROGRESS]);
            }

            if ($thread->status === SupportThread::STATUS_RESOLVED) {
                $thread->update(['status' => SupportThread::STATUS_OPEN]);
            }

            return $this->appendMessage($thread, [
                'user_id' => $admin->id,
                'sender_role' => SupportMessage::ROLE_ADMIN,
                'sender_name' => $admin->name,
                'body' => $body,
            ]);
        });
    }

    public function markCustomerMessagesAsRead(SupportThread $thread): int
    {
        return $thread->messages()
            ->where('sender_role', SupportMessage::ROLE_CUSTOMER)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }
}
