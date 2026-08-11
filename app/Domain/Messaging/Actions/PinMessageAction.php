<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Enums\MessageType;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Message;
use App\Models\User;

/**
 * Pins or unpins a message within its conversation.
 *
 * ONE pin per conversation, enforced by unpinning whatever was pinned before.
 * A list of pins would need its own drawer and a way to choose between them;
 * a single pin is a bar at the top of the thread that always means the same
 * thing. Pinning a second message replaces the first rather than failing,
 * because "pin this instead" is what the gesture means.
 *
 * Any participant may pin. Restricting it to group admins was considered and
 * rejected: in a direct thread there is no admin, and in a group the pin is
 * visible to everyone and trivially reversible, so the blast radius does not
 * justify the extra concept.
 */
final class PinMessageAction
{
    public function handle(Message $message, User $user): bool
    {
        $conversation = $message->conversation;

        if (! $conversation->includes($user)) {
            throw MessagingException::notAParticipant();
        }

        // A system message is the thread narrating itself; pinning one would
        // pin "أنشأ فلان المجموعة" to the top forever.
        if ($message->type === MessageType::System) {
            throw MessagingException::cannotPinSystemMessage();
        }

        if ($message->pinned_at !== null) {
            $message->forceFill(['pinned_at' => null, 'pinned_by' => null])->save();

            return false;
        }

        Message::query()
            ->where('conversation_id', $conversation->id)
            ->whereNotNull('pinned_at')
            ->update(['pinned_at' => null, 'pinned_by' => null]);

        $message->forceFill(['pinned_at' => now(), 'pinned_by' => $user->id])->save();

        return true;
    }
}
