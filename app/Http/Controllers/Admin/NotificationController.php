<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\PlatformNotification;
use App\Services\Admin\PlatformNotifications;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * Notifications Console (docs/MODULES.md §6, BR-804).
 */
class NotificationController extends Controller
{
    public function __construct(private PlatformNotifications $notifications) {}

    public function index(Request $request): View
    {
        $categories = [
            'all' => ['label' => 'الكل', 'phase4' => false],
            'approval' => ['label' => 'طلبات موافقة', 'phase4' => false],
            'security' => ['label' => 'تنبيهات أمنية', 'phase4' => false],
            'job_failed' => ['label' => 'مهام خلفية فاشلة', 'phase4' => false],
            'ops' => ['label' => 'تشغيلية', 'phase4' => false],
            'plan_limit' => ['label' => 'تحذيرات حدود الخطة', 'phase4' => true],
        ];

        $unreadCounts = $this->notifications->unreadCountsByCategory();

        $activeCategory = (string) $request->query('category', 'all');

        if (! array_key_exists($activeCategory, $categories) || ($categories[$activeCategory]['phase4'] ?? false)) {
            $activeCategory = 'all';
        }

        $all = $this->notifications->all($activeCategory === 'all' ? null : $activeCategory);

        $groups = [
            'today' => ['label' => 'اليوم', 'items' => array_values(array_filter($all, fn ($n): bool => $n['group'] === 'today'))],
            'week' => ['label' => 'هذا الأسبوع', 'items' => array_values(array_filter($all, fn ($n): bool => $n['group'] === 'week'))],
            'older' => ['label' => 'أقدم', 'items' => array_values(array_filter($all, fn ($n): bool => $n['group'] === 'older'))],
        ];

        return view('admin.notifications.index', [
            'categories' => $categories,
            'unreadCounts' => $unreadCounts,
            'activeCategory' => $activeCategory,
            'groups' => $groups,
        ]);
    }

    public function markAllRead(): RedirectResponse
    {
        $this->notifications->markAllAsRead();

        flash()->success('تم تحديد جميع الإشعارات كمقروءة.');

        return back();
    }

    public function destroyAll(): RedirectResponse
    {
        $this->notifications->destroyAll();

        flash()->info('تم مسح جميع الإشعارات.');

        return back();
    }

    public function markRead(PlatformNotification $notification): RedirectResponse
    {
        $notification->markAsRead();

        flash()->success('تم تحديث حالة الإشعار.');

        return back();
    }

    public function toggleRead(PlatformNotification $notification): RedirectResponse
    {
        if ($notification->isRead()) {
            $notification->markAsUnread();
            flash()->info('تم تحديد الإشعار كغير مقروء.');
        } else {
            $notification->markAsRead();
            flash()->success('تم تحديد الإشعار كمقروء.');
        }

        return back();
    }

    public function destroy(PlatformNotification $notification): RedirectResponse
    {
        $notification->delete();

        flash()->info('تم حذف الإشعار.');

        return back();
    }
}
