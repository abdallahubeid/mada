<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Message;
use App\Domain\Messaging\Models\MessageReaction;
use App\Models\User;

/**
 * Adds or removes one emoji reaction from one person on one message.
 *
 * Toggling rather than separate add/remove endpoints: tapping the same emoji
 * twice is how every messenger behaves, and modelling it as one idempotent
 * operation means a replayed request or a double-tap cannot inflate a count.
 */
final class ToggleReactionAction
{
    /**
     * The palette offered in the UI.
     *
     * Whitelisted, not free-form. `emoji` is rendered straight into the thread
     * for every participant, so accepting arbitrary strings would make the
     * reaction bar an injection surface and would also let one person write a
     * paragraph into everyone else's message.
     *
     * @var list<string>
     */
    public const ALLOWED = ['👍', '❤️', '😂', '😮', '😢', '🙏'];

    /**
     * @return bool true when the reaction was added, false when removed
     */
    public function handle(Message $message, User $user, string $emoji): bool
    {
        // Reacting is reading: the reactor must be in the conversation.
        if (! $message->conversation->includes($user)) {
            throw MessagingException::notAParticipant();
        }

        if (! in_array($emoji, self::ALLOWED, true)) {
            throw MessagingException::unsupportedReaction();
        }

        $existing = MessageReaction::query()
            ->where('message_id', $message->id)
            ->where('user_id', $user->id)
            ->where('emoji', $emoji)
            ->first();

        if ($existing !== null) {
            $existing->delete();

            return false;
        }

        MessageReaction::create([
            'message_id' => $message->id,
            'user_id' => $user->id,
            'emoji' => $emoji,
        ]);

        return true;
    }
}
