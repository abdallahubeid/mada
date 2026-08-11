<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Models\User;

/**
 * Copies a message into another conversation.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * MEMBERSHIP IS CHECKED ON BOTH ENDS
 *
 * Forwarding is the one operation that moves content ACROSS the privacy
 * boundary, so it verifies the caller is in the SOURCE thread (they may not
 * forward what they cannot read) and in the DESTINATION thread (they may not
 * post where they do not belong). Checking only one side would turn forwarding
 * into a way to exfiltrate from a thread or inject into one.
 *
 * A copy, not a move: the original stays, and the copy carries no `parent_id`
 * because the message it replied to does not exist in the destination — a
 * dangling quote would render as a reply to nothing.
 * ─────────────────────────────────────────────────────────────────────────
 */
final class ForwardMessageAction
{
    public function __construct(private readonly SendMessageAction $send) {}

    public function handle(Message $message, Conversation $destination, User $user): Message
    {
        if (! $message->conversation->includes($user)) {
            throw MessagingException::notAParticipant();
        }

        if (! $destination->includes($user)) {
            throw MessagingException::notAParticipant();
        }

        $body = trim((string) $message->body);

        if ($body === '') {
            throw MessagingException::emptyMessage();
        }

        // Routed through SendMessageAction rather than inserting directly, so a
        // forward broadcasts, bumps `last_message_at` and un-hides the thread
        // exactly like any other message. A direct insert would arrive silently.
        return $this->send->handle($destination, $user, $body);
    }
}
