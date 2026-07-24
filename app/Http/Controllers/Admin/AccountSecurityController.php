<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Admin Account Security (docs/MODULES.md §6, ADR-14). The self-service surface
 * where a Super Admin manages their own password, mandatory 2FA enrolment, and
 * active sessions. Frontend slice: sessions and 2FA state are mocked
 * in-controller.
 */
class AccountSecurityController extends Controller
{
    public function __invoke(): View
    {
        $twoFactor = [
            'enabled' => true,
            'confirmed_at' => '2026-05-14',
        ];

        $recoveryCodes = [
            'a1b2-c3d4-e5f6',
            'g7h8-i9j0-k1l2',
            'm3n4-o5p6-q7r8',
            's9t0-u1v2-w3x4',
            'y5z6-a7b8-c9d0',
            'e1f2-g3h4-i5j6',
            'k7l8-m9n0-o1p2',
            'q3r4-s5t6-u7v8',
        ];

        $sessions = [
            ['device' => 'Windows 11', 'browser' => 'Chrome 126', 'location' => 'الرياض، السعودية', 'ip' => '156.203.44.10', 'last_active' => 'نشطة الآن', 'current' => true],
            ['device' => 'macOS Sonoma', 'browser' => 'Safari 17', 'location' => 'جدة، السعودية', 'ip' => '92.117.88.4', 'last_active' => 'قبل ساعتين', 'current' => false],
            ['device' => 'iPhone 15', 'browser' => 'Safari Mobile', 'location' => 'الدمام، السعودية', 'ip' => '188.55.201.33', 'last_active' => 'أمس', 'current' => false],
            ['device' => 'Ubuntu 24.04', 'browser' => 'Firefox 127', 'location' => 'دبي، الإمارات', 'ip' => '5.32.144.9', 'last_active' => 'قبل 3 أيام', 'current' => false],
        ];

        return view('admin.account.security', [
            'twoFactor' => $twoFactor,
            'recoveryCodes' => $recoveryCodes,
            'sessions' => $sessions,
        ]);
    }
}
