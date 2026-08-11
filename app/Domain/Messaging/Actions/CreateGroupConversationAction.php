<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\EmployeeDirectory;
use App\Domain\Messaging\Enums\ConversationType;
use App\Domain\Messaging\Enums\MessageType;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\ConversationParticipant;
use App\Domain\Messaging\Models\Message;
use App\Models\User;
use Illuminate\Support\Facades\DB;

/**
 * Opens a group thread. Managers and Owners only.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY A PERMISSION HERE, WHEN READING IS NOT A PERMISSION
 *
 * Reading a conversation can never be a permission, because `Gate::before`
 * grants the Owner every ability and would hand them every private thread.
 *
 * Creating a group is different in kind: it grants the holder no sight of
 * anything they were not already part of — the group starts empty of history
 * and they are in it. So it is a real capability, `messaging.groups.create`,
 * which also extends to custom tenant roles that a hardcoded role-name check
 * would silently exclude.
 *
 * The creator is always a participant, and always the group's admin: a group
 * whose creator could leave themselves out would be a way to fabricate a
 * conversation between other people.
 * ─────────────────────────────────────────────────────────────────────────
 */
final class CreateGroupConversationAction
{
    public function __construct(private readonly EmployeeDirectory $directory) {}

    /**
     * @param  list<int>  $memberIds
     */
    public function handle(User $creator, string $title, array $memberIds): Conversation
    {
        if (! $creator->can('messaging.groups.create')) {
            throw MessagingException::cannotCreateGroups();
        }

        $title = trim($title);

        if ($title === '') {
            throw MessagingException::groupTitleRequired();
        }

        /*
         * Every member is re-checked through the directory. The picker only
         * offers reachable colleagues, but these ids arrive from a request
         * body — without this a manager could post arbitrary ids and pull a
         * user from another tenant into a thread, which is the one thing the
         * whole isolation design exists to prevent.
         */
        $members = array_values(array_unique(array_map('intval', $memberIds)));

        foreach ($members as $memberId) {
            if (! $this->directory->canReach($creator, $memberId)) {
                throw MessagingException::recipientUnavailable();
            }
        }

        if ($members === []) {
            throw MessagingException::groupNeedsMembers();
        }

        return DB::transaction(function () use ($creator, $title, $members): Conversation {
            $conversation = Conversation::create([
                'type' => ConversationType::Group,
                'title' => $title,
                'created_by' => $creator->id,
                // Null for groups: the same people may legitimately have
                // several distinct groups, so no uniqueness applies.
                'participants_hash' => null,
            ]);

            ConversationParticipant::create([
                'conversation_id' => $conversation->id,
                'user_id' => $creator->id,
                'role' => 'admin',
                'joined_at' => now(),
            ]);

            foreach ($members as $memberId) {
                if ($memberId === $creator->id) {
                    continue;
                }

                ConversationParticipant::create([
                    'conversation_id' => $conversation->id,
                    'user_id' => $memberId,
                    'joined_at' => now(),
                ]);
            }

            /*
             * A system message rather than silence: a group that appears in
             * someone's list with no explanation of who made it or when reads
             * as a bug. It also gives the thread a `last_message_at` so it
             * sorts sensibly before anyone has spoken.
             */
            $opened = Message::create([
                'conversation_id' => $conversation->id,
                'sender_id' => null,
                'type' => MessageType::System,
                'body' => "أنشأ {$creator->name} المجموعة",
                'sent_at' => now(),
            ]);

            $conversation->forceFill(['last_message_at' => $opened->sent_at])->save();

            return $conversation;
        });
    }
}
