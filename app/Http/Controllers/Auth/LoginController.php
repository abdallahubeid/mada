<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\LoginRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Unified login for Super Admin, CEO/Owner, and Employees
 * (docs/USER_JOURNEYS.md). Post-authentication redirects branch on the
 * account's onboarding progress: platform operators go to the admin console,
 * unverified emails go back to the verification notice, verified users on a
 * not-yet-active tenant land on the pending setup screen, and everyone else
 * reaches the tenant app.
 */
class LoginController extends Controller
{
    /**
     * Display the login form.
     */
    public function create(): View
    {
        return view('auth.login');
    }

    /**
     * Authenticate the request's credentials and redirect appropriately.
     */
    public function store(LoginRequest $request): RedirectResponse
    {
        $request->authenticate();

        $request->session()->regenerate();

        $user = Auth::user();

        if ($user->canAccessPlatformConsole()) {
            return redirect()->intended(route($user->preferredAdminHomeRoute()));
        }

        if (! $user->hasVerifiedEmail()) {
            return redirect()->route('verification.notice');
        }

        if (! $user->tenant?->isActive()) {
            return redirect()->route('dashboard.setup');
        }

        return redirect()->intended(route('dashboard'));
    }
}
