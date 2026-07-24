<?php

namespace App\Http\Controllers;

use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

/**
 * The holding screen a verified user lands on while their tenant is still
 * `pending_approval` (docs/ARCHITECTURE.md §3, BR-203). Once a Super Admin
 * approves the tenant, this route hands off to the real dashboard instead.
 */
class DashboardSetupController extends Controller
{
    public function __invoke(Request $request): View|RedirectResponse
    {
        $tenant = $request->user()->tenant;

        if ($tenant?->isActive()) {
            return redirect()->route('dashboard');
        }

        return view('dashboard.setup', ['tenant' => $tenant]);
    }
}
