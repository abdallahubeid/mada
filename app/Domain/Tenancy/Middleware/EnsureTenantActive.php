<?php

namespace App\Domain\Tenancy\Middleware;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

/**
 * Gates every operational route (HR, ATS, Finance, Payroll) behind an active tenant.
 *
 * Responsibilities, per docs/ARCHITECTURE.md §1.3 and §3 (BR-203):
 *
 *   1. Resolve the tenant for the authenticated user and bind it into
 *      {@see TenantContext} — the single source of truth every tenant-scoped
 *      query and every Spatie Teams permission check relies on.
 *   2. Reject the request unless that tenant's status is `active`.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHY ONBOARDING IS NOT GATED HERE
 *
 * The setup wizard is reachable by a pending tenant because those routes are
 * registered under `tenant.context` and never under `tenant.active` — the
 * isolation is structural, in routes/tenant.php, not a branch in this class.
 * That is deliberate: a middleware that let *some* requests through based on a
 * route-name allowlist would put the list of onboarding routes in two places
 * and eventually let an operational route match it.
 *
 * So this class stays a pure "active or not" gate, and the only thing that
 * varies is WHY the caller was refused — a pending tenant is told it may keep
 * completing its company profile, a suspended one is not.
 * ─────────────────────────────────────────────────────────────────────────
 *
 * Register this middleware on every route group that exposes operational
 * (non-setup, non-public, non-Super-Admin) functionality.
 */
class EnsureTenantActive
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 401);

        $tenant = $user->tenant;

        abort_if($tenant === null, 403, 'This account is not associated with a tenant.');

        $this->tenantContext->setTenant($tenant);

        abort_unless($tenant->isActive(), 403, $this->refusalMessage($tenant));

        return $next($request);
    }

    /**
     * The Arabic explanation shown on the 403 page, specific to why the tenant
     * cannot proceed. A single generic string here would tell a suspended
     * tenant to go finish its onboarding, which is both wrong and a support
     * ticket.
     */
    private function refusalMessage(Tenant $tenant): string
    {
        return match ($tenant->status) {
            TenantStatus::PendingVerification => 'يرجى تأكيد بريدك الإلكتروني لمتابعة تفعيل الحساب. يمكنك استكمال بيانات الشركة في هذه الأثناء.',
            TenantStatus::PendingApproval => 'حسابك قيد المراجعة - يمكنك استكمال بيانات الشركة لحين تفعيل الحساب',
            TenantStatus::Rejected => 'تم رفض طلب تسجيل هذه المؤسسة. للاستفسار عن الأسباب يرجى التواصل مع فريق الدعم.',
            TenantStatus::Suspended => 'تم إيقاف حساب مؤسستك مؤقتاً. يرجى التواصل مع فريق الدعم لإعادة التفعيل.',
            TenantStatus::Cancelled => 'تم إلغاء اشتراك هذه المؤسسة. يرجى التواصل مع فريق المبيعات لإعادة التفعيل.',
            TenantStatus::Active => 'هذا الحساب غير نشط حالياً.',
        };
    }
}
