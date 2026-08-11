<?php

namespace App\Domain\Messaging\Actions;

use App\Domain\Messaging\EmployeeDirectory;
use App\Domain\Messaging\Enums\ConversationType;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;

/**
 * Opens (or re-opens) the one direct thread between two colleagues.
 *
 * Idempotent by design: pressing "message" on someone you already have a
 * thread with must land in that thread, not create a second one.
 */
final class StartDirectConversationAction
{
    public function __construct(private readonly EmployeeDirectory $directory) {}

    public function handle(User $initiator, int $targetUserId): Conversation
    {
        /*
         * Re-checks the directory rather than trusting the id in the request.
         * The picker only ever renders reachable colleagues, but the id
         * arrives from the client and could name anyone in the database —
         * including a user in another tenant, or an employee with no login.
         */
        if (! $this->directory->canReach($initiator, $targetUserId)) {
            throw MessagingException::recipientUnavailable();
        }

        $hash = Conversation::hashFor(ConversationType::Direct, [$initiator->id, $targetUserId]);

        $existing = Conversation::query()->where('participants_hash', $hash)->first();

        if ($existing !== null) {
            return $existing;
        }

        try {
            return DB::transaction(function () use ($initiator, $targetUserId, $hash): Conversation {
                $conversation = Conversation::create([
                    'type' => ConversationType::Direct,
                    'created_by' => $initiator->id,
                    'participants_hash' => $hash,
                ]);

                foreach ([$initiator->id, $targetUserId] as $userId) {
                    ConversationParticipant::create([
                        'conversation_id' => $conversation->id,
                        'user_id' => $userId,
                        'joined_at' => now(),
                    ]);
                }

                return $conversation;
            });
        } catch (QueryException $exception) {
            /*
             * Both colleagues pressed "message" at the same instant: each
             * request found no existing thread, both inserted, and the unique
             * index on (tenant_id, participants_hash) refused the loser.
             *
             * That refusal is the feature — it is what stops the pair ending
             * up with two half-histories. The loser simply reads the winner's
             * row. Re-thrown if it was some other constraint, because
             * swallowing every QueryException here would hide real faults.
             */
            $winner = Conversation::query()->where('participants_hash', $hash)->first();

            if ($winner === null) {
                throw $exception;
            }

            return $winner;
        }
    }
}
