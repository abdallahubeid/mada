<?php

namespace App\Domain\Messaging\Support;

use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\ConversationParticipant;
use App\Models\User;
use Illuminate\Support\Carbon;

/**
 * Who is online, who has read what — and who is allowed to know.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * EVERY ANSWER HERE IS FILTERED BY THE TWO PRIVACY TOGGLES
 *
 * `chat_hide_last_seen` and `chat_hide_read_receipts` already existed on
 * `users` and were already editable in the messenger's privacy modal — they
 * simply had nothing reading them. This class is that reader, and it is the
 * ONLY one: the controller and the views ask this class, so a toggle cannot
 * be honoured in one surface and forgotten in another.
 *
 * The two toggles deliberately behave DIFFERENTLY, because the modal's own
 * copy promises different things:
 *
 *   «لن يرى زملاؤك حالتك أو وقت آخر ظهور لك»
 *       → one-directional. Hiding your presence hides it from others; it does
 *         not blind you to theirs.
 *
 *   «لن يرى الآخرون أنك قرأت رسائلهم، ولن ترى أنت مؤشرات قراءتهم»
 *       → symmetric, and stated as such. Hiding your read state also forfeits
 *         seeing everyone else's, so nobody can take without giving.
 *
 * The asymmetry between the two is the copy's, not an oversight — see the
 * note in the handover about whether last-seen should become symmetric too.
 * ─────────────────────────────────────────────────────────────────────────
 */
final class ConversationPresence
{
    /**
     * How recently someone must have been seen to count as online.
     *
     * Two minutes against a heartbeat that writes at most every 55 seconds:
     * one missed beat still reads as online, two does not. A tighter window
     * would flicker on any slow request.
     */
    public const ONLINE_WITHIN_MINUTES = 2;

    public function isOnline(?Carbon $lastSeenAt): bool
    {
        return $lastSeenAt !== null
            && $lastSeenAt->greaterThan(now()->subMinutes(self::ONLINE_WITHIN_MINUTES));
    }

    /**
     * The presence line under a thread title.
     *
     * @return array{visible: bool, online: bool, label: ?string}
     */
    public function peerStatus(Conversation $conversation, User $viewer): array
    {
        // A group has no single "other person" to be online, and showing the
        // most recently active member would quietly leak one member's
        // presence to everyone else in the room.
        if ($conversation->isGroup()) {
            return $this->concealed();
        }

        $peer = $conversation->participants
            ->firstWhere(fn (ConversationParticipant $p): bool => $p->user_id !== $viewer->id)
            ?->user;

        if ($peer === null || $peer->chat_hide_last_seen) {
            return $this->concealed();
        }

        if ($this->isOnline($peer->last_seen_at)) {
            return ['visible' => true, 'online' => true, 'label' => 'متصل الآن'];
        }

        return [
            'visible' => true,
            'online' => false,
            // `locale('ar')` explicitly: APP_LOCALE is `en`, so a bare
            // diffForHumans() renders «آخر ظهور 7 minutes ago» — English
            // inside an Arabic sentence. Same idiom as
            // PlatformNotifications and the newsletter dashboard.
            'label' => $peer->last_seen_at !== null
                ? 'آخر ظهور '.$peer->last_seen_at->locale('ar')->diffForHumans()
                : null,
        ];
    }

    /**
     * Whether a direct thread's other party is online, for the sidebar dot.
     *
     * Null — not false — when there is nothing to show: a group, a peer who
     * has hidden their presence, or a thread whose peer no longer exists. The
     * caller renders a dot only for `true`, so a hidden peer is indistinguishable
     * from an offline one, which is the point.
     */
    public function peerOnline(Conversation $conversation, User $viewer): ?bool
    {
        $status = $this->peerStatus($conversation, $viewer);

        return $status['visible'] ? $status['online'] : null;
    }

    /**
     * The highest message id every other participant has read.
     *
     * Null means "show no read state at all", which covers the privacy opt-out
     * on either side as well as a thread nobody has read yet.
     */
    public function readWatermark(Conversation $conversation, User $viewer): ?int
    {
        // Symmetry, enforced first: someone who hides their own read state
        // does not get to see anyone else's.
        if ($viewer->chat_hide_read_receipts) {
            return null;
        }

        $others = $conversation->participants
            ->filter(fn (ConversationParticipant $p): bool => $p->user_id !== $viewer->id);

        if ($others->isEmpty()) {
            return null;
        }

        // One opted-out member suppresses the whole thread's receipts. The
        // alternative — reporting the watermark of everyone else — would say
        // "read by all except one", and in a three-person group that names
        // the person who opted out.
        if ($others->contains(fn (ConversationParticipant $p): bool => (bool) $p->user?->chat_hide_read_receipts)) {
            return null;
        }

        // MIN, not MAX: a double tick has to mean everyone has seen it. In a
        // direct thread the two are identical; in a group, MAX would mark a
        // message read the moment the fastest member opened it.
        $watermark = (int) $others
            ->map(fn (ConversationParticipant $p): int => (int) ($p->last_read_message_id ?? 0))
            ->min();

        return $watermark > 0 ? $watermark : null;
    }

    /**
     * @return array{visible: bool, online: bool, label: ?string}
     */
    private function concealed(): array
    {
        return ['visible' => false, 'online' => false, 'label' => null];
    }
}
