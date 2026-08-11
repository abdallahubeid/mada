<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Message;
use App\Models\User;

/**
 * Soft-deletes a message.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ONLY THE AUTHOR MAY DELETE
 *
 * Not the group admin, not the Owner, not a platform Super Admin. Letting
 * anyone else remove a message would make the thread unreliable as a record —
 * the point of "من قال ماذا" is that nobody else can edit the answer.
 *
 * Soft, not hard: the row stays for retention and audit, and `parent_id` on
 * any reply keeps resolving rather than orphaning the quote.
 * ─────────────────────────────────────────────────────────────────────────
 */
final class DeleteMessageAction
{
    public function handle(Message $message, User $user): void
    {
        if (! $message->conversation->includes($user)) {
            throw MessagingException::notAParticipant();
        }

        if ($message->sender_id !== $user->id) {
            throw MessagingException::cannotDeleteOthersMessages();
        }

        // A pinned message that vanished would leave the pinned bar quoting
        // text no longer in the thread.
        if ($message->pinned_at !== null) {
            $message->forceFill(['pinned_at' => null, 'pinned_by' => null])->save();
        }

        $message->delete();
    }
}
