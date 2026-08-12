<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Enums\MessageType;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Support\MessageAttachmentStorage;
use App\Events\Messaging\MessageSent;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Throwable;

/**
 * Posts a message to a conversation the sender belongs to.
 */
final class SendMessageAction
{
    public function __construct(private readonly MessageAttachmentStorage $attachments) {}

    /**
     * @param  list<UploadedFile>  $files
     */
    public function handle(
        Conversation $conversation,
        User $sender,
        string $body,
        ?int $parentId = null,
        array $files = [],
    ): Message {
        if (! $conversation->includes($sender)) {
            throw MessagingException::notAParticipant();
        }

        $body = trim($body);

        /*
         * A message must carry SOMETHING, but that something may be a file.
         *
         * The empty check is on body-and-files together rather than on body
         * alone: sending a photo with no caption is the normal case, and the
         * previous rule would have rejected it as an empty message.
         */
        if ($body === '' && $files === []) {
            throw MessagingException::emptyMessage();
        }

        /*
         * A reply must point at a message in THIS thread. Without the check a
         * client could quote any message id in the tenant, and the reply
         * preview would render a line from a conversation the reader is not
         * in — a privacy leak through the quote block rather than the thread.
         */
        if ($parentId !== null) {
            $parentExists = Message::query()
                ->whereKey($parentId)
                ->where('conversation_id', $conversation->id)
                ->exists();

            if (! $parentExists) {
                $parentId = null;
            }
        }

        /*
         * Files are written to disk BEFORE the transaction opens.
         *
         * Writing them inside would hold the row locks for the duration of the
         * upload, and a filesystem write is not covered by a rollback anyway.
         * Instead the bytes land first and are swept up explicitly if the
         * transaction throws — the failure mode being an orphaned file, never
         * an attachment row pointing at nothing.
         */
        $descriptors = $files === [] ? [] : $this->attachments->put($conversation, $files);

        try {
            $message = DB::transaction(function () use ($conversation, $sender, $body, $parentId, $descriptors): Message {
                $message = Message::create([
                    'conversation_id' => $conversation->id,
                    'sender_id' => $sender->id,
                    'type' => MessageType::Text,
                    'body' => $body,
                    'parent_id' => $parentId,
                    'sent_at' => now(),
                ]);

                foreach ($descriptors as $descriptor) {
                    $message->attachments()->create($descriptor);
                }

                // Denormalised onto the thread so the conversation list can sort
                // without touching this table.
                $conversation->forceFill(['last_message_at' => $message->sent_at])->save();

                /*
                 * The sender has, by definition, read what they just wrote.
                 * Advancing their own watermark here keeps a thread from showing
                 * its author an unread badge for their own message.
                 */
                $conversation->participants()
                    ->where('user_id', $sender->id)
                    ->update([
                        'last_read_message_id' => $message->id,
                        'last_read_at' => now(),
                    ]);

                /*
                 * A new message un-hides the thread for everyone who had removed
                 * it from their list. Without this, "حذف المحادثة" would silently
                 * swallow every subsequent message from that colleague — the user
                 * asked to clear a thread, not to block a person.
                 *
                 * Archiving is NOT cleared: archiving is a filing decision the
                 * owner made deliberately, and un-filing it on every reply would
                 * make the archive useless.
                 */
                $conversation->participants()
                    ->whereNotNull('hidden_at')
                    ->update(['hidden_at' => null]);

                return $message;
            });
        } catch (Throwable $exception) {
            // The rows are gone; the bytes are not. Sweep them, or every failed
            // send leaves a file on disk that nothing will ever reference.
            $this->attachments->discard($descriptors);

            throw $exception;
        }

        /*
         * Broadcast AFTER the transaction commits. Dispatching inside it would
         * let a subscriber receive — and render — a message that a subsequent
         * rollback erased, leaving a bubble on screen that exists nowhere in
         * the database and vanishes on refresh.
         */
        $message->setRelation('sender', $sender);
        $message->load('attachments');
        MessageSent::dispatch($message);

        return $message;
    }
}
