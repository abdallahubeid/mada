<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\SupportThread;
use App\Services\Support\SupportInbox;
use App\Services\Support\SupportInboxPoller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;

/**
 * Messages & Support Inquiries inbox (docs/MODULES.md §6, BR-805/BR-806).
 */
class MessageController extends Controller
{
    public function __construct(
        private SupportInbox $inbox,
        private SupportInboxPoller $poller,
    ) {}

    public function index(Request $request): View
    {
        $tabs = [
            SupportThread::STATUS_OPEN => 'مفتوح',
            SupportThread::STATUS_IN_PROGRESS => 'قيد المعالجة',
            SupportThread::STATUS_RESOLVED => 'تم الحل',
            SupportThread::STATUS_ARCHIVED => 'مؤرشف',
        ];

        $activeStatus = (string) $request->query('status', SupportThread::STATUS_OPEN);

        if (! array_key_exists($activeStatus, $tabs)) {
            $activeStatus = SupportThread::STATUS_OPEN;
        }

        $search = trim((string) $request->query('q', ''));
        $requestedId = (int) $request->query('thread', 0);

        $list = $this->poller->listThreads($activeStatus, $search, $requestedId);

        // Only open a thread when explicitly requested — never auto-select the first.
        $selected = null;
        $selectedMessages = [];

        if ($requestedId > 0) {
            $selected = SupportThread::query()
                ->with(['messages.user.avatar', 'user.avatar'])
                ->find($requestedId);

            if ($selected !== null && $selected->status === $activeStatus) {
                $this->inbox->markCustomerMessagesAsRead($selected);
                $selected->unsetRelation('messages');
                $selected->load(['messages.user.avatar', 'user.avatar']);
                $selectedMessages = $selected->messages
                    ->map(fn ($message) => $this->poller->serializeMessage($message))
                    ->values()
                    ->all();
            } else {
                $selected = null;
            }
        }

        return view('admin.messages.index', [
            'threads' => $list['threads'],
            'tabs' => $tabs,
            'counts' => $list['counts'],
            'activeStatus' => $activeStatus,
            'selected' => $selected,
            'selectedMessages' => $selectedMessages,
            'search' => $search,
            'pollSignature' => $list['signature'],
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $activeStatus = (string) $request->query('status', SupportThread::STATUS_OPEN);

        if (! array_key_exists($activeStatus, SupportInboxPoller::STATUS_META)) {
            $activeStatus = SupportThread::STATUS_OPEN;
        }

        $search = trim((string) $request->query('q', ''));
        $selectedThreadId = (int) $request->query('thread', 0);
        $afterMessageId = max(0, (int) $request->query('after_message_id', 0));

        $list = $this->poller->listThreads($activeStatus, $search, $selectedThreadId);

        // Poll only returns messages newer than the client's last id (never a full replay).
        $messagesPayload = $afterMessageId > 0
            ? $this->poller->messagesSince($selectedThreadId, $afterMessageId)
            : [
                'messages' => [],
                'selected_exists' => $selectedThreadId <= 0
                    || SupportThread::query()->whereKey($selectedThreadId)->exists(),
            ];

        return response()->json([
            'counts' => $list['counts'],
            'threads' => $list['threads'],
            'signature' => $list['signature'],
            'messages' => $messagesPayload['messages'],
            'selected_exists' => $messagesPayload['selected_exists'],
        ]);
    }

    public function reply(Request $request, SupportThread $thread): RedirectResponse
    {
        $validated = $request->validate([
            'body' => ['required', 'string', 'min:1', 'max:5000'],
        ]);

        $admin = Auth::user();

        abort_unless($admin !== null, 403);

        $this->inbox->replyAsAdmin($thread, $admin, $validated['body']);

        flash()->success('تم إرسال الرد بنجاح.');

        return redirect()->route('admin.messages', [
            'status' => $thread->fresh()?->status ?? SupportThread::STATUS_IN_PROGRESS,
            'thread' => $thread->id,
        ]);
    }

    public function updateStatus(Request $request, SupportThread $thread): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in([
                SupportThread::STATUS_OPEN,
                SupportThread::STATUS_IN_PROGRESS,
                SupportThread::STATUS_RESOLVED,
                SupportThread::STATUS_ARCHIVED,
            ])],
        ]);

        $thread->update(['status' => $validated['status']]);

        $message = match ($validated['status']) {
            SupportThread::STATUS_ARCHIVED => 'تم أرشفة المحادثة بنجاح.',
            SupportThread::STATUS_RESOLVED => 'تم تعليم المحادثة كمحلولة.',
            default => 'تم تحديث حالة المحادثة.',
        };

        flash()->info($message);

        return redirect()->route('admin.messages', [
            'status' => $validated['status'],
            'thread' => $thread->id,
        ]);
    }

    public function archive(SupportThread $thread): RedirectResponse
    {
        $thread->update(['status' => SupportThread::STATUS_ARCHIVED]);

        flash()->info('تم أرشفة المحادثة بنجاح.');

        return redirect()->route('admin.messages', [
            'status' => SupportThread::STATUS_ARCHIVED,
        ]);
    }

    public function destroy(SupportThread $thread): JsonResponse
    {
        $thread->delete();

        return response()->json([
            'success' => true,
            'message' => 'تم حذف المحادثة بنجاح.',
        ]);
    }
}
