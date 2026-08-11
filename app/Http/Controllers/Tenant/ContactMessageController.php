<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\TenantContactThread;
use App\Http\Controllers\Controller;
use App\Services\Tenancy\TenantContactInbox;
use App\Services\Tenancy\TenantContactInboxPoller;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Tenant portal contact messages inbox.
 */
class ContactMessageController extends Controller
{
    public function __construct(
        private TenantContactInbox $inbox,
        private TenantContactInboxPoller $poller,
        private TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $search = trim((string) $request->query('q', ''));
        $list = $this->poller->listThreads(TenantContactInboxPoller::FOLDER_ACTIVE, $search);

        return view('tenant.contact-messages.index', [
            'threads' => $list['threads'],
            'counts' => $list['counts'],
            'search' => $search,
            'canManage' => Auth::user()?->can('tenant.contact_messages.manage') ?? false,
            'threadsUrl' => route('tenant.contact-messages.threads'),
        ]);
    }

    public function threads(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'folder' => ['nullable', Rule::in([
                TenantContactInboxPoller::FOLDER_ACTIVE,
                TenantContactInboxPoller::FOLDER_ARCHIVED,
            ])],
            'q' => ['nullable', 'string', 'max:255'],
        ]);

        $folder = $validated['folder'] ?? TenantContactInboxPoller::FOLDER_ACTIVE;
        $search = trim((string) ($validated['q'] ?? ''));
        $selectedId = (int) $request->query('thread', 0);

        $list = $this->poller->listThreads($folder, $search, $selectedId);

        return response()->json([
            'success' => true,
            ...$list,
        ]);
    }

    public function show(TenantContactThread $thread): JsonResponse
    {
        $this->inbox->markVisitorMessagesAsRead($thread);

        $thread->load(['messages.user.avatar', 'latestMessage']);

        $messages = $thread->messages
            ->map(fn ($message) => $this->poller->serializeMessage($message))
            ->values()
            ->all();

        return response()->json([
            'success' => true,
            'thread' => $this->poller->serializeThread($thread, $thread->id),
            'messages' => $messages,
            'can_manage' => Auth::user()?->can('tenant.contact_messages.manage') ?? false,
            'can_reply' => (Auth::user()?->can('tenant.contact_messages.manage') ?? false) && ! $thread->isArchived(),
        ]);
    }

    public function reply(Request $request, TenantContactThread $thread): JsonResponse
    {
        abort_if($thread->isArchived(), 404);

        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $staff = Auth::user();

        abort_unless($staff !== null, 403);

        $message = $this->inbox->replyAsStaff($thread, $staff, $validated['body']);
        $message->load('user.avatar');

        return response()->json([
            'success' => true,
            'message' => 'تم إرسال الرد بنجاح.',
            'thread' => $this->poller->serializeThread($thread->fresh(['latestMessage']) ?? $thread, $thread->id),
            'chat_message' => $this->poller->serializeMessage($message),
        ]);
    }

    public function archive(TenantContactThread $thread): JsonResponse
    {
        abort_if($thread->isArchived(), 422);

        $this->inbox->archive($thread);

        return response()->json([
            'success' => true,
            'message' => 'تم نقل المحادثة إلى الأرشيف',
            'thread_id' => $thread->id,
        ]);
    }

    public function unarchive(TenantContactThread $thread): JsonResponse
    {
        abort_unless($thread->isArchived(), 422);

        $this->inbox->unarchive($thread);

        return response()->json([
            'success' => true,
            'message' => 'تم إعادة المحادثة إلى الرسائل النشطة',
            'thread_id' => $thread->id,
        ]);
    }

    public function destroy(TenantContactThread $thread): JsonResponse
    {
        $threadId = $thread->id;
        $this->inbox->deleteThread($thread);

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المحادثة بنجاح',
            'thread_id' => $threadId,
            ...$this->trash->undoPayload('contact-messages', $thread),
        ]);
    }
}
