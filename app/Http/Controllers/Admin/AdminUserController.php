<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;

/**
 * Super Admin User Management (docs/MODULES.md §6, BR-807/BR-808). Platform
 * operator accounts created directly by a Super Admin (temporary password +
 * mandatory 2FA enrolment) with last-admin lockout safeguards. Frontend slice:
 * operator rows are mocked in-controller.
 */
class AdminUserController extends Controller
{
    public function index(): View
    {
        $admins = [
            ['name' => 'سلمان العتيبي', 'email' => 'salman@veyra.app', 'role' => 'super_admin', 'status' => 'active', 'two_factor' => true, 'created_at' => '2025-11-02', 'is_self' => true],
            ['name' => 'ريم الدوسري', 'email' => 'reem@veyra.app', 'role' => 'super_admin', 'status' => 'active', 'two_factor' => true, 'created_at' => '2026-01-18', 'is_self' => false],
            ['name' => 'فهد القرني', 'email' => 'fahd@veyra.app', 'role' => 'support_admin', 'status' => 'active', 'two_factor' => true, 'created_at' => '2026-03-05', 'is_self' => false],
            ['name' => 'ليلى الحربي', 'email' => 'laila@veyra.app', 'role' => 'support_admin', 'status' => 'active', 'two_factor' => false, 'created_at' => '2026-04-22', 'is_self' => false],
            ['name' => 'ماجد الشهري', 'email' => 'majed@veyra.app', 'role' => 'support_admin', 'status' => 'suspended', 'two_factor' => true, 'created_at' => '2026-02-11', 'is_self' => false],
        ];

        $metrics = [
            ['label' => 'إجمالي المشرفين', 'value' => count($admins)],
            ['label' => 'نشطون', 'value' => count(array_filter($admins, fn ($a): bool => $a['status'] === 'active'))],
            ['label' => 'بانتظار إعداد 2FA', 'value' => count(array_filter($admins, fn ($a): bool => ! $a['two_factor']))],
            ['label' => 'موقوفون', 'value' => count(array_filter($admins, fn ($a): bool => $a['status'] === 'suspended'))],
        ];

        return view('admin.admins.index', [
            'admins' => $admins,
            'metrics' => $metrics,
        ]);
    }
}
