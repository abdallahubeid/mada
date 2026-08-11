<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\Eloquent\Builder;

/**
 * Archive, unarchive, or hide a conversation — always for ONE participant.
 *
 * Nothing here writes to the conversation or to any message. "حذف المحادثة"
 * means "take it off my list", not "destroy the thread": the other participant
 * keeps their copy, and no history is lost. Anything stronger is a retention
 * decision rather than a button.
 */
final class ShelveConversationAction
{
    public function archive(Conversation $conversation, User $user): void
    {
        $this->participantRow($conversation, $user)
            ->update(['archived_at' => now(), 'hidden_at' => null]);
    }

    public function unarchive(Conversation $conversation, User $user): void
    {
        $this->participantRow($conversation, $user)
            ->update(['archived_at' => null]);
    }

    /**
     * Remove the thread from this user's list.
     *
     * Deliberately reversible by the OTHER party: a new message clears
     * `hidden_at` (see SendMessageAction), because a thread that stayed hidden
     * while a colleague kept writing would silently swallow their messages.
     */
    public function hide(Conversation $conversation, User $user): void
    {
        $this->participantRow($conversation, $user)
            ->update(['hidden_at' => now(), 'archived_at' => null]);
    }

    /**
     * @return Builder<ConversationParticipant>
     */
    private function participantRow(Conversation $conversation, User $user)
    {
        if (! $conversation->includes($user)) {
            throw MessagingException::notAParticipant();
        }

        return $conversation->participants()->where('user_id', $user->id);
    }
}
