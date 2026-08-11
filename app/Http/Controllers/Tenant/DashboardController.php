<?php

namespace App\Http\Controllers\Tenant;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Tenancy\ExecutiveDashboard;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly ExecutiveDashboard $dashboard) {}

    /**
     * Role-aware entry point for `/app/dashboard`.
     *
     * Kept as the single post-login destination so every existing link, the
     * sidebar brand mark, and the login redirect keep working. It only routes:
     * an Owner falls through to the executive dashboard rendered here, while
     * HR Managers and employees are redirected to their own dedicated route so
     * each role still gets a distinct, bookmarkable URL.
     *
     * Order matters — Owners hold every permission via the Gate::before bypass,
     * so the Owner check has to come first or they would be routed to HR.
     *
     * Within the non-Owner branch the order is Finance -> HR -> Employee. A
     * Finance Manager holds `hr.my_dashboard.view` through the self-service
     * bucket, so a broader check placed first would swallow them and land them
     * on the employee dashboard instead of their own.
     */
    public function index(Request $request): View|RedirectResponse
    {
        $user = $request->user();

        if ($user instanceof User && ! $user->isOwner()) {
            if ($user->can('finance.dashboard.view')) {
                return redirect()->route('tenant.finance.dashboard');
            }

            if ($user->can('hr.dashboard.view')) {
                return redirect()->route('tenant.hr.dashboard');
            }

            if ($user->can('hr.my_dashboard.view')) {
                return redirect()->route('tenant.hr.employee.dashboard');
            }
        }

        return view('tenant.dashboard.index', $this->dashboard->build());
    }
}
