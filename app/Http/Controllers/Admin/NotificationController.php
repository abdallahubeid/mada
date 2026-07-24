<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Notifications Console (docs/MODULES.md §6, BR-804). Surfaces the four
 * platform alert categories to the Super Admin; the `plan_limit` category
 * stays empty until Phase 4's `CheckFeatureLimit` ships. Frontend slice:
 * notifications are mocked in-controller and filtered by the `category` param.
 */
class NotificationController extends Controller
{
    /**
     * @return list<array{category:string, title:string, body:string, target:string|null, time:string, read:bool, group:string}>
     */
    private function notifications(): array
    {
        return [
            ['category' => 'approval', 'title' => 'مستأجر جديد بانتظار الموافقة', 'body' => 'قدّمت «شركة الأفق للتقنية» طلب تسجيل وتنتظر مراجعتك.', 'target' => 'admin.tenants', 'time' => 'قبل 15 دقيقة', 'read' => false, 'group' => 'today'],
            ['category' => 'security', 'title' => 'محاولات دخول فاشلة متكررة', 'body' => 'رُصدت 5 محاولات دخول فاشلة على حساب مشرف خلال دقيقتين.', 'target' => 'admin.audit-log', 'time' => 'قبل ساعة', 'read' => false, 'group' => 'today'],
            ['category' => 'approval', 'title' => 'مستأجر جديد بانتظار الموافقة', 'body' => 'قدّمت «مؤسسة نماء» طلب تسجيل وتنتظر مراجعتك.', 'target' => 'admin.tenants', 'time' => 'قبل 3 ساعات', 'read' => false, 'group' => 'today'],
            ['category' => 'job_failed', 'title' => 'فشل تنفيذ مهمة خلفية', 'body' => 'فشلت مهمة إرسال بريد التحقق (SendVerificationEmail) 3 مرات.', 'target' => null, 'time' => 'قبل 5 ساعات', 'read' => true, 'group' => 'today'],
            ['category' => 'security', 'title' => 'تسجيل دخول من موقع جديد', 'body' => 'سُجّل دخول لحسابك من عنوان IP غير معهود.', 'target' => 'admin.account.security', 'time' => 'أمس', 'read' => true, 'group' => 'week'],
            ['category' => 'approval', 'title' => 'مستأجر جديد بانتظار الموافقة', 'body' => 'قدّمت «مجموعة رواد» طلب تسجيل وتنتظر مراجعتك.', 'target' => 'admin.tenants', 'time' => 'قبل يومين', 'read' => true, 'group' => 'week'],
            ['category' => 'job_failed', 'title' => 'فشل تنفيذ مهمة خلفية', 'body' => 'فشلت مهمة مزامنة الفواتير الليلية (SyncInvoices).', 'target' => null, 'time' => 'قبل 4 أيام', 'read' => true, 'group' => 'week'],
            ['category' => 'security', 'title' => 'تغيير كلمة مرور مشرف', 'body' => 'تم تحديث كلمة مرور حساب مشرف بنجاح.', 'target' => 'admin.audit-log', 'time' => 'قبل 12 يومًا', 'read' => true, 'group' => 'older'],
            ['category' => 'approval', 'title' => 'تمت الموافقة على مستأجر', 'body' => 'فُعّل حساب «شركة الابتكار» بنجاح.', 'target' => 'admin.tenants', 'time' => 'قبل 20 يومًا', 'read' => true, 'group' => 'older'],
        ];
    }

    public function __invoke(Request $request): View
    {
        $all = $this->notifications();

        $categories = [
            'all' => ['label' => 'الكل', 'phase4' => false],
            'approval' => ['label' => 'طلبات موافقة', 'phase4' => false],
            'security' => ['label' => 'تنبيهات أمنية', 'phase4' => false],
            'job_failed' => ['label' => 'مهام خلفية فاشلة', 'phase4' => false],
            'plan_limit' => ['label' => 'تحذيرات حدود الخطة', 'phase4' => true],
        ];

        // Unread counts per category (drives the chip badges).
        $unreadCounts = ['all' => count(array_filter($all, fn ($n): bool => ! $n['read']))];

        foreach (['approval', 'security', 'job_failed', 'plan_limit'] as $cat) {
            $unreadCounts[$cat] = count(array_filter($all, fn ($n): bool => $n['category'] === $cat && ! $n['read']));
        }

        $activeCategory = $request->query('category', 'all');

        if (! array_key_exists($activeCategory, $categories) || $activeCategory === 'plan_limit') {
            $activeCategory = 'all';
        }

        $filtered = $activeCategory === 'all'
            ? $all
            : array_values(array_filter($all, fn ($n): bool => $n['category'] === $activeCategory));

        // Group by timeframe for the feed.
        $groups = [
            'today' => ['label' => 'اليوم', 'items' => array_values(array_filter($filtered, fn ($n): bool => $n['group'] === 'today'))],
            'week' => ['label' => 'هذا الأسبوع', 'items' => array_values(array_filter($filtered, fn ($n): bool => $n['group'] === 'week'))],
            'older' => ['label' => 'أقدم', 'items' => array_values(array_filter($filtered, fn ($n): bool => $n['group'] === 'older'))],
        ];

        return view('admin.notifications.index', [
            'categories' => $categories,
            'unreadCounts' => $unreadCounts,
            'activeCategory' => $activeCategory,
            'groups' => $groups,
        ]);
    }
}
