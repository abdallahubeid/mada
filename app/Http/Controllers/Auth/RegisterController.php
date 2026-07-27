<?php

namespace App\Http\Controllers\Auth;

use App\Domain\Tenancy\Actions\SeedDefaultTenantRoles;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Auth\RegisterRequest;
use App\Models\User;
use App\Services\Admin\PlatformNotificationPublisher;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;

/**
 * Handles the SaaS multi-step registration wizard (docs/USER_JOURNEYS.md
 * — Onboarding): user credentials, organization details, and plan
 * selection are all submitted together and turned into a new tenant
 * (status: pending_verification) plus its Owner user.
 */
class RegisterController extends Controller
{
    public function __construct(
        private readonly SeedDefaultTenantRoles $seedDefaultTenantRoles,
        private readonly TenantContext $tenantContext,
        private readonly PlatformNotificationPublisher $notifications,
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
        $data = $request->validated();

        $tenant = null;

        $user = DB::transaction(function () use ($data, &$tenant): User {
            $tenant = Tenant::create([
                'name' => $data['company_name'],
                'slug' => $data['company_slug'],
                'status' => TenantStatus::PendingVerification,
                'industry' => $data['industry'],
                'team_size' => $data['team_size'],
                'plan' => $data['plan'],
            ]);

            $this->seedDefaultTenantRoles->handle($tenant);

            $user = User::create([
                'tenant_id' => $tenant->id,
                'name' => $data['name'],
                'email' => $data['email'],
                'password' => $data['password'],
            ]);

            $this->tenantContext->setTenant($tenant);
            $user->assignRole('Owner');

            return $user;
        });

        if ($tenant instanceof Tenant) {
            $this->notifications->tenantRegisteredPendingApproval($tenant);
        }

        Auth::login($user);

        $user->sendEmailVerificationNotification();

        return redirect()->route('verification.notice');
    }
}
