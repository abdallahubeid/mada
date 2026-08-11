<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantContactMessage;
use App\Domain\Tenancy\Models\TenantContactThread;
use App\Events\Tenant\NewContactMessageReceived;
use App\Mail\Tenant\ContactMessageReply;
use App\Models\User;
use App\Notifications\Tenant\NewContactMessageNotification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Creates / appends tenant portal contact threads and staff replies.
 */
class TenantContactInbox
{
    public function __construct(private TenantOwnerNotifier $notifier) {}

    /**
     * Find a thread for the email (same tenant) or create one, then append the visitor message.
     *
     * @param  array{name: string, email: string, subject: string, message: string}  $inquiry
     */
    public function ingestPortalInquiry(Tenant $tenant, array $inquiry): TenantContactThread
    {
        $result = DB::transaction(function () use ($tenant, $inquiry): array {
            $email = strtolower(trim($inquiry['email']));

            $thread = TenantContactThread::query()
                ->where('tenant_id', $tenant->id)
                ->active()
                ->whereRaw('LOWER(sender_email) = ?', [$email])
                ->latest('last_message_at')
                ->lockForUpdate()
                ->first();

            if ($thread === null) {
                $thread = TenantContactThread::query()->create([
                    'tenant_id' => $tenant->id,
                    'sender_name' => $inquiry['name'],
                    'sender_email' => $email,
                    'subject' => $inquiry['subject'],
                    'status' => TenantContactThread::STATUS_OPEN,
                    'last_message_at' => now(),
                ]);
            } else {
                $thread->fill([
                    'sender_name' => $inquiry['name'],
                    'subject' => $inquiry['subject'] ?: $thread->subject,
                    'last_message_at' => now(),
                ])->save();
            }

            $message = $thread->messages()->create([
                'tenant_id' => $tenant->id,
                'user_id' => null,
                'sender_role' => TenantContactMessage::ROLE_VISITOR,
                'sender_name' => $inquiry['name'],
                'body' => $inquiry['message'],
                'delivered_at' => null,
                'read_at' => null,
            ]);

            return [
                'thread' => $thread->fresh(['messages', 'latestMessage']) ?? $thread,
                'message' => $message,
            ];
        });

        /** @var TenantContactThread $thread */
        $thread = $result['thread'];
        /** @var TenantContactMessage $message */
        $message = $result['message'];

        $this->broadcastAndMarkDelivered($tenant, $thread, $message);

        return $thread->fresh(['messages', 'latestMessage']) ?? $thread;
    }

    public function replyAsStaff(TenantContactThread $thread, User $staff, string $body, bool $sendEmail = true): TenantContactMessage
    {
        $message = DB::transaction(function () use ($thread, $staff, $body): TenantContactMessage {
            $message = $thread->messages()->create([
                'tenant_id' => $thread->tenant_id,
                'user_id' => $staff->id,
                'sender_role' => TenantContactMessage::ROLE_STAFF,
                'sender_name' => $staff->name,
                'body' => $body,
                'delivered_at' => now(),
                'read_at' => null,
            ]);

            $thread->forceFill(['last_message_at' => now()])->save();

            return $message;
        });

        if ($sendEmail) {
            Mail::to($thread->sender_email)->send(new ContactMessageReply(
                $thread->fresh() ?? $thread,
                $message,
            ));
        }

        return $message;
    }

    public function markVisitorMessagesAsRead(TenantContactThread $thread): int
    {
        return $thread->messages()
            ->where('sender_role', TenantContactMessage::ROLE_VISITOR)
            ->whereNull('read_at')
            ->update(['read_at' => now()]);
    }

    public function archive(TenantContactThread $thread): TenantContactThread
    {
        $thread->update(['status' => TenantContactThread::STATUS_ARCHIVED]);

        return $thread->fresh() ?? $thread;
    }

    public function unarchive(TenantContactThread $thread): TenantContactThread
    {
        $thread->update(['status' => TenantContactThread::STATUS_OPEN]);

        return $thread->fresh() ?? $thread;
    }

    public function deleteThread(TenantContactThread $thread): void
    {
        $thread->delete();
    }

    private function broadcastAndMarkDelivered(
        Tenant $tenant,
        TenantContactThread $thread,
        TenantContactMessage $message,
    ): void {
        $owners = $this->notifier->ownersForTenant($tenant->id);
        $ownerIds = $owners->modelKeys();

        // Delivered = persisted + about to fan-out on Reverb (double gray ✓✓).
        $message->forceFill(['delivered_at' => now()])->save();

        event(new NewContactMessageReceived(
            $tenant->id,
            $thread->fresh(['latestMessage']) ?? $thread,
            $message->fresh() ?? $message,
            $ownerIds,
        ));

        $this->notifier->send(
            $tenant->id,
            fn (): NewContactMessageNotification => new NewContactMessageNotification(
                $thread->fresh() ?? $thread,
                $message->fresh() ?? $message,
            ),
        );
    }
}
