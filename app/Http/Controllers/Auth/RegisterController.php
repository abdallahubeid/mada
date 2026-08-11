<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Tenancy\Actions\RegisterTenantAction;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;

/**
 * Handles the SaaS multi-step registration wizard (docs/USER_JOURNEYS.md
 * — Onboarding): user credentials, organization details, and plan
 * selection are all submitted together and turned into a new tenant
 * (status: pending_verification) plus its Owner user.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly RegisterTenantAction $registerTenant,
    ) {}

    /**
     * Display the registration wizard.
     */
    public function create(): View
    {
        return view('auth.register', [
            'industries' => RegisterRequest::INDUSTRIES,
            'teamSizes' => RegisterRequest::TEAM_SIZES,
            'plans' => RegisterRequest::PLANS,
        ]);
    }

    /**
     * Create the tenant and its Owner user, then send the user on to
     * verify their email address.
     */
    public function store(RegisterRequest $request): RedirectResponse
    {
        // Tenant + Owner creation lives in the action so the ordering it
        // depends on (tenant → roles → owner, all inside the tenant context)
        // has exactly one implementation.
        [$tenant, $user] = $this->registerTenant->handle($request->validated());

        /*
         * The "awaiting your review" notification is NOT published here. It used
         * to be, which announced a tenant to the Super Admin while it was still
         * `pending_verification` — the body rendered that status verbatim, the
         * approve action would have refused it, and the operator was sent to
         * review something not yet reviewable. It now fires from
         * VerifyTenantEmailAction, at the moment the tenant actually enters the
         * queue.
         */
        Auth::login($user);

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
