<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Super Admin Profile (docs/MODULES.md §6). Self-service surface for the logged-in
 * operator's personal details, interface preferences, and notification opt-ins.
 * Frontend slice: the profile is mocked in-controller; persistence lands with the
 * backend phase.
 */
class ProfileController extends Controller
{
    public function index(): View
    {
        $profile = [
            'name' => 'عبد الله خالد عبيد',
            'email' => 'abdullah@veyra.app',
            'email_verified' => true,
            'phone' => '+966 55 123 4567',
            'role' => 'super_admin',
            'role_label' => 'مشرف عام - Super Admin',
            'avatar' => null,
            'language' => 'ar',
            'theme' => 'system',
        ];

        $notificationPreferences = [
            ['key' => 'security_alerts', 'label' => 'التنبيهات الأمنية', 'desc' => 'محاولات دخول مشبوهة أو تغييرات على الحساب.', 'enabled' => true],
            ['key' => 'tenant_requests', 'label' => 'طلبات المستأجرين', 'desc' => 'إشعار عند وجود مستأجر جديد بانتظار الموافقة.', 'enabled' => true],
            ['key' => 'system_errors', 'label' => 'أخطاء النظام', 'desc' => 'فشل المهام الخلفية والأخطاء الحرجة في المنصّة.', 'enabled' => false],
            ['key' => 'support_updates', 'label' => 'تحديثات تذاكر الدعم', 'desc' => 'ردود جديدة على محادثات الدعم الفني.', 'enabled' => true],
        ];

        return view('admin.profile.index', [
            'profile' => $profile,
            'notificationPreferences' => $notificationPreferences,
        ]);
    }
}
