<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Services\Admin\AdminDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Super Admin Dashboard — platform health at a glance (docs/MODULES.md §6).
 */
class DashboardController extends Controller
{
    public function __invoke(Request $request, AdminDashboard $dashboard): View
    {
        $payload = $dashboard->build((string) $request->query('range', '30d'));

        return view('admin.dashboard', $payload);
    }
}
