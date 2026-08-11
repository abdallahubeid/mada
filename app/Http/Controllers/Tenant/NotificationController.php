<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Notifications\DatabaseNotification;

class NotificationController extends Controller
{
    public function index(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->tenant_id !== null, 403);

        $notifications = $user->notifications()
            ->latest()
            ->limit(30)
            ->get()
            ->map(fn (DatabaseNotification $notification): array => $this->transform($notification));

        return response()->json([
            'unread_count' => $user->unreadNotifications()->count(),
            'notifications' => $notifications,
        ]);
    }

    public function markAsRead(Request $request, string $notification): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->tenant_id !== null, 403);

        $model = $user->notifications()->whereKey($notification)->firstOrFail();
        $model->markAsRead();

        return response()->json([
            'ok' => true,
            'unread_count' => $user->unreadNotifications()->count(),
        ]);
    }

    public function markAllAsRead(Request $request): JsonResponse
    {
        $user = $request->user();
        abort_unless($user !== null && $user->tenant_id !== null, 403);

        $user->unreadNotifications->markAsRead();

        return response()->json([
            'ok' => true,
            'unread_count' => 0,
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function transform(DatabaseNotification $notification): array
    {
        $data = is_array($notification->data) ? $notification->data : [];

        return [
            'id' => $notification->id,
            'title' => (string) ($data['title'] ?? 'إشعار'),
            'message' => (string) ($data['message'] ?? ''),
            'url' => $data['url'] ?? null,
            'icon' => (string) ($data['icon'] ?? 'bell'),
            'severity' => (string) ($data['severity'] ?? 'medium'),
            'type' => (string) ($data['type'] ?? ''),
            'read_at' => optional($notification->read_at)?->toIso8601String(),
            'created_at' => optional($notification->created_at)?->toIso8601String(),
        ];
    }
}
