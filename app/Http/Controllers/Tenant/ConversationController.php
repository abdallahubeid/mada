<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Messaging\Actions\CreateGroupConversationAction;
use App\Domain\Messaging\Actions\DeleteMessageAction;
use App\Domain\Messaging\Actions\ForwardMessageAction;
use App\Domain\Messaging\Actions\PinMessageAction;
use App\Domain\Messaging\Actions\SendMessageAction;
use App\Domain\Messaging\Actions\ShelveConversationAction;
use App\Domain\Messaging\Actions\StartDirectConversationAction;
use App\Domain\Messaging\Actions\ToggleReactionAction;
use App\Domain\Messaging\EmployeeDirectory;
use App\Domain\Messaging\Exceptions\MessagingException;
use App\Domain\Messaging\Models\Conversation;
use App\Domain\Messaging\Models\Message;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

/**
 * The internal messenger (Phase 1: direct threads, list + thread panes).
 *
 * ─────────────────────────────────────────────────────────────────────────
 * EVERY READ PATH GOES THROUGH `visibleTo($user)`
 *
 * The tenant global scope keeps other companies out; `visibleTo` keeps
 * COLLEAGUES out. Both are required on every query in this class, and the
 * second is not expressible as a permission — see ConversationChannel for why
 * a Gate check would hand Owners every thread in the company.
 *
 * `findOrFail` through that scope also means a non-participant gets a 404
 * rather than a 403: a 403 would confirm the conversation exists.
 * ─────────────────────────────────────────────────────────────────────────
 */
class ConversationController extends Controller
{
    public function index(Request $request, EmployeeDirectory $directory): View
    {
        $user = $request->user();

        // inboxFor, not visibleTo: shelved threads stay accessible by URL and
        // keep receiving messages, they just leave the sidebar.
        $conversations = Conversation::query()
            ->inboxFor($user)
            ->with(['participants.user'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return view('tenant.messenger.index', [
            'conversations' => $this->summarize($conversations, $user),
            'directory' => $directory->for($user),
            'activeConversation' => null,
            'messages' => collect(),
            'pinnedMessage' => null,
            'canCreateGroups' => $user->can('messaging.groups.create'),
            'reactionPalette' => ToggleReactionAction::ALLOWED,
        ]);
    }

    public function show(Request $request, int $conversation, EmployeeDirectory $directory): View
    {
        $user = $request->user();

        $record = Conversation::query()
            ->visibleTo($user)
            ->with(['participants.user'])
            ->findOrFail($conversation);

        $messages = Message::query()
            ->page($record->id, null, 30)
            // Reactions eager-loaded with the page: rendering them lazily would
            // fire a query per bubble, which on a 30-message page is 30 queries
            // for a feature most messages do not use.
            ->with(['sender', 'reactions'])
            ->get()
            // Paged newest-first for the keyset seek, rendered oldest-first.
            ->reverse()
            ->values();

        // At most one per thread, so this is a single indexed lookup rather
        // than a filter over the loaded page — the pin may well be older than
        // the 30 messages currently on screen.
        $pinnedMessage = Message::query()
            ->where('conversation_id', $record->id)
            ->whereNotNull('pinned_at')
            ->with('sender')
            ->first();

        $this->markRead($record, $request);

        // inboxFor, not visibleTo: shelved threads stay accessible by URL and
        // keep receiving messages, they just leave the sidebar.
        $conversations = Conversation::query()
            ->inboxFor($user)
            ->with(['participants.user'])
            ->orderByDesc('last_message_at')
            ->orderByDesc('id')
            ->get();

        return view('tenant.messenger.index', [
            'conversations' => $this->summarize($conversations, $user),
            'directory' => $directory->for($user),
            'activeConversation' => $record,
            'activeTitle' => $record->displayTitleFor($user),
            'messages' => $messages,
            'pinnedMessage' => $pinnedMessage,
            'canCreateGroups' => $user->can('messaging.groups.create'),
            'reactionPalette' => ToggleReactionAction::ALLOWED,
        ]);
    }

    public function store(Request $request, StartDirectConversationAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        try {
            $conversation = $action->handle($request->user(), (int) $validated['user_id']);
        } catch (MessagingException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        return redirect()->route('tenant.messenger.show', $conversation->id);
    }

    public function send(Request $request, int $conversation, SendMessageAction $action): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        $validated = $request->validate([
            'body' => ['required', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
        ]);

        $record = Conversation::query()->visibleTo($user)->findOrFail($conversation);

        try {
            $message = $action->handle(
                $record,
                $user,
                (string) $validated['body'],
                isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
            );
        } catch (MessagingException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            flash()->error($exception->getMessage());

            return back();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $message->id,
                'body' => $message->body,
                'sender_id' => $message->sender_id,
                'sent_at' => $message->sent_at?->toIso8601String(),
            ], 201);
        }

        return redirect()->route('tenant.messenger.show', $record->id);
    }

    public function storeGroup(Request $request, CreateGroupConversationAction $action): RedirectResponse
    {
        $validated = $request->validate([
            'title' => ['required', 'string', 'max:120'],
            'members' => ['required', 'array', 'min:1'],
            'members.*' => ['integer'],
        ]);

        try {
            $conversation = $action->handle(
                $request->user(),
                (string) $validated['title'],
                $validated['members'],
            );
        } catch (MessagingException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        return redirect()->route('tenant.messenger.show', $conversation->id);
    }

    public function react(Request $request, int $message, ToggleReactionAction $action): JsonResponse
    {
        $validated = $request->validate([
            'emoji' => ['required', 'string', 'max:32'],
        ]);

        // Reached through visibleTo so a message in someone else's thread is a
        // 404 — the same "never confirm it exists" rule as the thread itself.
        $record = $this->findMessageFor($request, $message);

        try {
            $added = $action->handle($record, $request->user(), (string) $validated['emoji']);
        } catch (MessagingException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json([
            'added' => $added,
            'counts' => $this->reactionCounts($record),
        ]);
    }

    public function pin(Request $request, int $message, PinMessageAction $action): JsonResponse
    {
        $record = $this->findMessageFor($request, $message);

        try {
            $pinned = $action->handle($record, $request->user());
        } catch (MessagingException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['pinned' => $pinned]);
    }

    public function destroyMessage(Request $request, int $message, DeleteMessageAction $action): JsonResponse
    {
        $record = $this->findMessageFor($request, $message);

        try {
            $action->handle($record, $request->user());
        } catch (MessagingException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['deleted' => true, 'id' => $record->id]);
    }

    public function forward(Request $request, int $message, ForwardMessageAction $action): JsonResponse
    {
        $validated = $request->validate([
            'conversation_id' => ['required', 'integer'],
        ]);

        $record = $this->findMessageFor($request, $message);

        // Resolved through visibleTo so a destination the caller is not in is a
        // 404 — forwarding must not become a way to post into someone's thread.
        $destination = Conversation::query()
            ->visibleTo($request->user())
            ->findOrFail((int) $validated['conversation_id']);

        try {
            $copy = $action->handle($record, $destination, $request->user());
        } catch (MessagingException $exception) {
            return response()->json(['message' => $exception->getMessage()], 422);
        }

        return response()->json(['forwarded' => true, 'conversation_id' => $destination->id, 'id' => $copy->id], 201);
    }

    public function archive(Request $request, int $conversation, ShelveConversationAction $action): JsonResponse
    {
        $record = Conversation::query()->visibleTo($request->user())->findOrFail($conversation);

        $request->boolean('undo')
            ? $action->unarchive($record, $request->user())
            : $action->archive($record, $request->user());

        return response()->json(['archived' => ! $request->boolean('undo')]);
    }

    /**
     * "حذف المحادثة" — removes the thread from the caller's list only.
     *
     * The conversation and every message survive: the other participant keeps
     * their copy. A new message un-hides it again.
     */
    public function hide(Request $request, int $conversation, ShelveConversationAction $action): JsonResponse
    {
        $record = Conversation::query()->visibleTo($request->user())->findOrFail($conversation);

        $action->hide($record, $request->user());

        return response()->json(['hidden' => true]);
    }

    public function updatePrivacy(Request $request): RedirectResponse
    {
        $user = $request->user();

        // `boolean()` rather than reading the key: an unchecked checkbox sends
        // nothing at all, so a missing key means false, not "leave unchanged".
        $user->forceFill([
            'chat_hide_last_seen' => $request->boolean('chat_hide_last_seen'),
            'chat_hide_read_receipts' => $request->boolean('chat_hide_read_receipts'),
        ])->save();

        flash()->success('تم تحديث إعدادات خصوصية المراسلات.');

        return back();
    }

    /**
     * A message, but only if the caller can see the thread it belongs to.
     */
    private function findMessageFor(Request $request, int $messageId): Message
    {
        $message = Message::query()->with('conversation')->findOrFail($messageId);

        Conversation::query()
            ->visibleTo($request->user())
            ->findOrFail($message->conversation_id);

        return $message;
    }

    /**
     * @return array<string, int>
     */
    private function reactionCounts(Message $message): array
    {
        return $message->reactions()
            ->selectRaw('emoji, count(*) as total')
            ->groupBy('emoji')
            ->pluck('total', 'emoji')
            ->map(fn ($total): int => (int) $total)
            ->all();
    }

    /**
     * Advance the reader's watermark to the newest message in the thread.
     *
     * One UPDATE of one row, whatever the thread's length — the reason read
     * state is a watermark rather than a row per message.
     */
    private function markRead(Conversation $conversation, Request $request): void
    {
        $latestId = Message::query()
            ->where('conversation_id', $conversation->id)
            ->max('id');

        if ($latestId === null) {
            return;
        }

        $conversation->participants()
            ->where('user_id', $request->user()->id)
            ->update([
                'last_read_message_id' => $latestId,
                'last_read_at' => now(),
            ]);
    }

    /**
     * Shape the thread list for the sidebar.
     *
     * @param  Collection<int, Conversation>  $conversations
     * @return list<array<string, mixed>>
     */
    private function summarize($conversations, $user): array
    {
        return $conversations->map(function (Conversation $conversation) use ($user): array {
            $mine = $conversation->participants->firstWhere('user_id', $user->id);

            return [
                'id' => $conversation->id,
                'title' => $conversation->displayTitleFor($user),
                'initial' => mb_substr($conversation->displayTitleFor($user), 0, 1),
                'is_group' => $conversation->isGroup(),
                'last_message_at' => $conversation->last_message_at?->diffForHumans(),
                'unread' => $mine?->unreadCount() ?? 0,
            ];
        })->all();
    }
}
