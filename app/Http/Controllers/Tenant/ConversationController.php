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
use App\Domain\Messaging\Models\MessageAttachment;
use App\Domain\Messaging\Support\ConversationPresence;
use App\Domain\Messaging\Support\MessageAttachmentStorage;
use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

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
    public function __construct(private readonly ConversationPresence $presence) {}

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
            'peerStatus' => ['visible' => false, 'online' => false, 'label' => null],
            'readWatermark' => null,
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
            //
            // `parent.sender` for the same reason — the quote block above a
            // reply names the person being replied to, so a lazy load would be
            // two queries per reply. The parent is fetched through the relation
            // rather than matched against the loaded page because the message
            // being replied to is often older than the 30 on screen.
            //
            // `attachments` likewise — a thread of photos would otherwise be
            // one query per bubble.
            ->with(['sender', 'reactions', 'parent.sender', 'attachments'])
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
            // Unaffected by the markRead() above, which advances the CALLER's
            // watermark through the query builder and leaves the loaded
            // relation stale. That staleness is harmless here: a read receipt
            // reports how far the OTHER participants have got, and the caller's
            // own row is never part of the answer.
            'peerStatus' => $this->presence->peerStatus($record, $user),
            'readWatermark' => $this->presence->readWatermark($record, $user),
        ]);
    }

    /**
     * Presence + read state for one thread, polled by the open messenger.
     *
     * Polled rather than broadcast because it must work with Reverb down —
     * which is its whole reason for existing. When Reverb is running this is
     * simply redundant, not conflicting: both paths write the same two values.
     */
    public function pulse(Request $request, int $conversation): JsonResponse
    {
        $user = $request->user();

        $record = Conversation::query()
            ->visibleTo($user)
            ->with(['participants.user'])
            ->findOrFail($conversation);

        return response()->json([
            'peer' => $this->presence->peerStatus($record, $user),
            'read_up_to' => $this->presence->readWatermark($record, $user),
        ]);
    }

    public function store(Request $request, StartDirectConversationAction $action): JsonResponse|RedirectResponse
    {
        $validated = $request->validate([
            'user_id' => ['required', 'integer'],
        ]);

        try {
            $conversation = $action->handle($request->user(), (int) $validated['user_id']);
        } catch (MessagingException $exception) {
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            flash()->error($exception->getMessage());

            return back();
        }

        // The JSON branch exists so the client can start a thread and then
        // move to it with Livewire.navigate() instead of a form POST plus a
        // 302, which reloads the document and throws away the open messenger.
        if ($request->expectsJson()) {
            return response()->json([
                'id' => $conversation->id,
                'url' => route('tenant.messenger.show', $conversation->id),
            ], 201);
        }

        return redirect()->route('tenant.messenger.show', $conversation->id);
    }

    public function send(Request $request, int $conversation, SendMessageAction $action): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        /*
         * ─────────────────────────────────────────────────────────────────
         * VALIDATED BY HAND, NOT WITH $request->validate()
         *
         * bootstrap/app.php narrows the exception handler to
         * `shouldRenderJsonWhen(fn ($request) => $request->is('api/*'))`, so a
         * ValidationException thrown from anywhere under /app renders as a 302
         * redirect EVEN WHEN the caller sent `Accept: application/json`.
         *
         * The composer posts over fetch. A 302 is followed transparently, the
         * client sees 200 with an HTML body, `response.ok` is true, and
         * `response.json()` throws on markup — a rejected upload would look
         * like a successful send that silently dropped the file.
         *
         * Rejecting a file is a NORMAL outcome here (wrong type, too large),
         * so this path has to answer properly. Validating explicitly keeps the
         * fix local to the messenger instead of changing how every other
         * endpoint in the app reports validation failure.
         * ─────────────────────────────────────────────────────────────────
         */
        $validator = Validator::make($request->all(), [
            // `required_without` rather than `required`: a photo with no
            // caption is a message, and demanding body text would reject it.
            'body' => ['required_without:attachments', 'nullable', 'string', 'max:5000'],
            'parent_id' => ['nullable', 'integer'],
            'attachments' => ['nullable', 'array', 'max:'.MessageAttachmentStorage::MAX_FILES],
            'attachments.*' => [
                'file',
                'max:'.MessageAttachmentStorage::MAX_KILOBYTES,
                // Extension whitelist, validated against the file's real type
                // rather than its name — an .exe renamed to .pdf fails here.
                'mimes:'.MessageAttachmentStorage::ALLOWED_EXTENSIONS,
            ],
        ]);

        if ($validator->fails()) {
            if ($request->expectsJson()) {
                return response()->json([
                    'message' => 'تعذّر إرسال الرسالة.',
                    'errors' => $validator->errors()->toArray(),
                ], 422);
            }

            return back()->withErrors($validator)->withInput();
        }

        $validated = $validator->validated();

        $record = Conversation::query()->visibleTo($user)->findOrFail($conversation);

        try {
            $message = $action->handle(
                $record,
                $user,
                (string) ($validated['body'] ?? ''),
                isset($validated['parent_id']) ? (int) $validated['parent_id'] : null,
                $request->file('attachments', []),
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
                'attachments' => $this->describeAttachments($message),
            ], 201);
        }

        return redirect()->route('tenant.messenger.show', $record->id);
    }

    public function storeGroup(Request $request, CreateGroupConversationAction $action): JsonResponse|RedirectResponse
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
            if ($request->expectsJson()) {
                return response()->json(['message' => $exception->getMessage()], 422);
            }

            flash()->error($exception->getMessage());

            return back();
        }

        if ($request->expectsJson()) {
            return response()->json([
                'id' => $conversation->id,
                'url' => route('tenant.messenger.show', $conversation->id),
            ], 201);
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

    public function updatePrivacy(Request $request): JsonResponse|RedirectResponse
    {
        $user = $request->user();

        // `boolean()` rather than reading the key: an unchecked checkbox sends
        // nothing at all, so a missing key means false, not "leave unchanged".
        $user->forceFill([
            'chat_hide_last_seen' => $request->boolean('chat_hide_last_seen'),
            'chat_hide_read_receipts' => $request->boolean('chat_hide_read_receipts'),
        ])->save();

        // Saved over fetch so the open thread survives. The client re-pulses
        // afterwards: turning read receipts off has to make the ticks already
        // on screen disappear immediately, or the setting looks ignored.
        if ($request->expectsJson()) {
            return response()->json([
                'chat_hide_last_seen' => $user->chat_hide_last_seen,
                'chat_hide_read_receipts' => $user->chat_hide_read_receipts,
            ]);
        }

        flash()->success('تم تحديث إعدادات خصوصية المراسلات.');

        return back();
    }

    /**
     * Serve an image inline, for rendering inside a bubble.
     *
     * Separate from download() only in its Content-Disposition, and hard-gated
     * on the STORED mime being a raster image: anything else — an SVG, an
     * HTML file renamed to .png, a PDF — is forced to download instead. An
     * inline response is executed in this origin, so "which files may be
     * inline" is a security decision, not a presentation one.
     */
    public function previewAttachment(Request $request, int $attachment): StreamedResponse|Response
    {
        $record = $this->findAttachmentFor($request, $attachment);

        if (! MessageAttachmentStorage::isInlineSafe($record->mime_type)) {
            return $this->downloadAttachment($request, $attachment);
        }

        return Storage::disk($record->disk)->response(
            $record->path,
            $record->original_name,
            [
                // Belt and braces around an inline response:
                //   nosniff  — the browser must not second-guess the type we
                //              declare and decide the bytes are HTML.
                //   CSP      — even if it somehow rendered as a document,
                //              nothing in it may load or execute.
                'Content-Type' => $record->mime_type,
                'X-Content-Type-Options' => 'nosniff',
                'Content-Security-Policy' => "default-src 'none'; sandbox",
            ],
        );
    }

    /**
     * Serve any attachment as a download.
     */
    public function downloadAttachment(Request $request, int $attachment): StreamedResponse
    {
        $record = $this->findAttachmentFor($request, $attachment);

        // `original_name` is the uploader's, and Laravel escapes it into the
        // Content-Disposition header rather than interpolating it raw.
        return Storage::disk($record->disk)->download(
            $record->path,
            $record->original_name,
            ['X-Content-Type-Options' => 'nosniff'],
        );
    }

    /**
     * An attachment, but only if the caller is in the conversation it belongs to.
     *
     * ─────────────────────────────────────────────────────────────────────
     * THIS IS THE ONLY GATE ON THE FILE
     *
     * The bytes sit on a disk with no route and no public URL, so every read
     * passes through here. The check is the same participant-row lookup the
     * rest of the messenger uses — deliberately NOT a permission, because
     * `Gate::before` grants Owners every ability and would hand them every
     * file in the company.
     *
     * `findOrFail` through `visibleTo` means a non-participant gets 404, not
     * 403: a 403 would confirm the attachment exists. The default (non-trashed)
     * scope on the attachment is load-bearing too — a message deleted by its
     * author takes its files out of reach here.
     * ─────────────────────────────────────────────────────────────────────
     */
    private function findAttachmentFor(Request $request, int $attachmentId): MessageAttachment
    {
        $attachment = MessageAttachment::query()->findOrFail($attachmentId);

        Conversation::query()
            ->visibleTo($request->user())
            ->findOrFail($attachment->conversation_id);

        return $attachment;
    }

    /**
     * Attachment metadata for the client — never a path, never a disk.
     *
     * @return list<array<string, mixed>>
     */
    private function describeAttachments(Message $message): array
    {
        return $message->attachments->map(fn (MessageAttachment $attachment): array => [
            'id' => $attachment->id,
            'kind' => $attachment->kind,
            'name' => $attachment->original_name,
            'size' => $attachment->humanSize(),
            'preview_url' => $attachment->isImage()
                ? route('tenant.messenger.attachments.preview', $attachment->id)
                : null,
            'download_url' => route('tenant.messenger.attachments.download', $attachment->id),
        ])->all();
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
                // `locale('ar')` for the same reason as the presence label:
                // APP_LOCALE is `en`, so these sidebar timestamps have been
                // rendering «7 minutes ago» in an otherwise Arabic list.
                'last_message_at' => $conversation->last_message_at?->locale('ar')->diffForHumans(),
                'unread' => $mine?->unreadCount() ?? 0,
                // True, false, or null when there is nothing to disclose — a
                // group, or a peer who has hidden their presence. The card
                // renders a dot only for true, so "hidden" and "offline" are
                // deliberately indistinguishable from the outside.
                'peer_online' => $this->presence->peerOnline($conversation, $user),
            ];
        })->all();
    }
}
