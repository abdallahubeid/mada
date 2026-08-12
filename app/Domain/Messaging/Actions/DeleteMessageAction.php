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

        /*
         * Attachments go with the message.
         *
         * Soft-deleted, and the FILE IS KEPT — same retention rationale as the
         * message row. What changes is reachability: the download endpoint
         * resolves attachments through the default scope, so a soft-deleted
         * row 404s. Deleting a message therefore revokes access to its files
         * without destroying evidence.
         *
         * Without this the row would survive the message and its file would
         * stay downloadable by anyone who had already seen the id — deleting a
         * message would visibly remove it while quietly leaving the document
         * it carried available.
         */
        $message->attachments()->delete();

        $message->delete();
    }
}
