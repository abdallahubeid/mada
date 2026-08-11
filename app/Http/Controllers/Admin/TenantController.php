<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Tenancy\Actions\ApproveTenantAction;
use App\Domain\Tenancy\Actions\ReactivateTenantAction;
use App\Domain\Tenancy\Actions\RejectTenantAction;
use App\Domain\Tenancy\Actions\SuspendTenantAction;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Exceptions\TenantReviewException;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantPlanResolver;
use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\RejectTenantRequest;
use App\Http\Requests\Admin\SuspendTenantRequest;
use App\Http\Requests\Admin\UpdateTenantMarketingRequest;
use App\Models\Plan;
use App\Models\User;
use App\Services\Marketing\MarketingCache;
use App\Services\Media\ImageUploader;
use App\Services\Platform\PlatformAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

/**
 * Tenant Management — the tenant lifecycle system of record (docs/MODULES.md
 * §6, ARCHITECTURE.md §3, BR-205/BR-206).
 *
 * Rebuilt on real `Tenant` records 2026-08-09. This screen previously rendered
 * a hardcoded array of ten fictional companies, which meant the Super Admin
 * approve/reject buttons had nothing to act on — the Phase 1 exit criterion
 * "a Super Admin can approve/reject/suspend tenants" was not actually met.
 *
 * Every figure below is now sourced or omitted. Nothing is invented: there is
 * no "projects" count because the Projects module does not exist, and "last
 * activity" comes from the tenant's own audit log rather than a made-up
 * relative time.
 */
class TenantController extends Controller
{
    /**
     * Rows per page. The review queue is worked front to back, so this is sized
     * to a screenful rather than to a "load everything" habit.
     */
    private const PER_PAGE = 20;

    public function index(Request $request): View
    {
        $tabs = [
            'all' => 'الكل',
            TenantStatus::PendingApproval->value => 'بانتظار الموافقة',
            TenantStatus::PendingVerification->value => 'بانتظار التحقق',
            TenantStatus::Active->value => 'نشط',
            TenantStatus::Rejected->value => 'مرفوض',
            TenantStatus::Suspended->value => 'موقوف',
            TenantStatus::Cancelled->value => 'ملغى',
        ];

        $activeTab = (string) $request->query('status', TenantStatus::PendingApproval->value);

        if (! array_key_exists($activeTab, $tabs)) {
            $activeTab = TenantStatus::PendingApproval->value;
        }

        $search = trim((string) $request->query('q', ''));
        $planId = $request->query('plan');
        $planId = is_numeric($planId) ? (int) $planId : null;

        /*
         * Search and plan narrow the counts as well as the rows. A tab reading
         * "12" above a single filtered result would look like the filter had
         * silently failed, so the badge has to describe the same population the
         * table is drawn from — only the status facet differs between them.
         */
        $filtered = fn () => Tenant::query()
            ->when($search !== '', function ($query) use ($search): void {
                $like = '%'.str_replace(['%', '_'], ['\%', '\_'], $search).'%';

                $query->where(function ($inner) use ($like): void {
                    $inner->where('name', 'like', $like)
                        ->orWhere('slug', 'like', $like)
                        // Matches any user on the tenant, not only the Owner:
                        // support is usually handed the address of whoever
                        // wrote in, who is often not the registering account.
                        ->orWhereHas('users', fn ($users) => $users->where('email', 'like', $like));
                });
            })
            ->when($planId !== null, fn ($query) => $query->where('plan_id', $planId));

        $counts = ['all' => $filtered()->count()];

        foreach (TenantStatus::cases() as $status) {
            $counts[$status->value] = $filtered()->where('status', $status->value)->count();
        }

        $tenants = $filtered()
            ->with('plan')
            ->when($activeTab !== 'all', fn ($query) => $query->where('status', $activeTab))
            // Oldest application first: a review queue is worked front to back,
            // so the tenant that has waited longest must surface first.
            ->orderByRaw('case when status = ? then 0 else 1 end', [TenantStatus::PendingApproval->value])
            ->orderBy('created_at')
            ->paginate(self::PER_PAGE)
            // withQueryString, or paging away from page 1 would silently drop
            // the active tab and search and show an unfiltered page 2.
            ->withQueryString()
            ->through(fn (Tenant $tenant): array => $this->summarize($tenant));

        return view('admin.tenants.index', [
            'tenants' => $tenants,
            'tabs' => $tabs,
            'counts' => $counts,
            'activeTab' => $activeTab,
            'search' => $search,
            'planFilter' => $planId,
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function show(string $tenant, TenantPlanResolver $resolver): View
    {
        $record = Tenant::query()->with('plan', 'reviewer', 'suspender')->where('slug', $tenant)->firstOrFail();

        $owner = $this->ownerOf($record);
        $employees = $this->employeeCount($record);
        $departments = DB::table('departments')
            ->where('tenant_id', $record->id)
            ->whereNull('deleted_at')
            ->count();

        $detail = [
            ...$this->summarize($record),
            'departments' => $departments,
            'member_since' => $record->created_at?->translatedFormat('j F Y') ?? '—',
            'owner' => [
                'name' => $owner?->name ?? '—',
                'email' => $owner?->email ?? '—',
                'verified' => $owner?->email_verified_at !== null,
                'last_login' => $this->lastActivity($record) ?? '—',
            ],
            'usage' => $this->usage($record, $resolver, $employees, $departments),
            'audit' => $this->recentAudit($record),
            /*
             * Null unless the tenant is currently suspended — the columns are
             * cleared on reactivation, so this block renders only while the
             * suspension actually stands rather than as stale history beside a
             * green badge.
             */
            'suspension' => $record->suspended_at !== null ? [
                'reason' => $record->suspension_reason,
                'at' => $record->suspended_at->translatedFormat('j F Y — H:i'),
                'by' => $record->suspender?->name ?? 'النظام',
            ] : null,
            'marketing' => [
                'persistable' => true,
                'show_on_marketing' => $record->show_on_marketing,
                'logo_url' => $record->image('logo')->first()?->url()
                    ?? $record->image('marketing_logo')->first()?->url(),
            ],
        ];

        return view('admin.tenants.show', [
            'tenant' => $detail,
            'tenantRecord' => $record,
            'plans' => Plan::query()->where('is_active', true)->orderBy('sort_order')->get(),
        ]);
    }

    public function approve(Request $request, string $tenant, ApproveTenantAction $action): RedirectResponse
    {
        $record = Tenant::query()->where('slug', $tenant)->firstOrFail();

        $validated = $request->validate([
            'plan_id' => ['nullable', 'integer', 'exists:plans,id'],
        ]);

        try {
            $action->handle($record, $request->user(), $validated['plan_id'] ?? null);
        } catch (TenantReviewException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->success("تم تفعيل حساب «{$record->name}» وإشعار المالك بالبريد الإلكتروني.");

        return redirect()->route('admin.tenants.show', $record->slug);
    }

    public function reject(RejectTenantRequest $request, string $tenant, RejectTenantAction $action): RedirectResponse
    {
        $record = Tenant::query()->where('slug', $tenant)->firstOrFail();

        try {
            $action->handle($record, $request->user(), (string) $request->validated('rejection_reason'));
        } catch (TenantReviewException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        // `warning`, not `success`: a refusal is not an achievement, and the
        // toast tone has to match the outcome (DESIGN_SYSTEM.md §9).
        flash()->warning("تم رفض طلب «{$record->name}» وإشعار مقدّم الطلب بالسبب.");

        return redirect()->route('admin.tenants.show', $record->slug);
    }

    public function suspend(SuspendTenantRequest $request, string $tenant, SuspendTenantAction $action): RedirectResponse
    {
        $record = Tenant::query()->where('slug', $tenant)->firstOrFail();

        try {
            $action->handle($record, $request->user(), (string) $request->validated('suspension_reason'));
        } catch (TenantReviewException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        // `warning`, not `success`: locking a paying customer out of the
        // product is not an achievement, and the toast tone has to match the
        // outcome (DESIGN_SYSTEM.md §9).
        flash()->warning("تم إيقاف حساب «{$record->name}» وإشعار المالك بالسبب.");

        return redirect()->route('admin.tenants.show', $record->slug);
    }

    public function reactivate(Request $request, string $tenant, ReactivateTenantAction $action): RedirectResponse
    {
        $record = Tenant::query()->where('slug', $tenant)->firstOrFail();

        try {
            $action->handle($record, $request->user());
        } catch (TenantReviewException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->success("تم إعادة تفعيل حساب «{$record->name}» وإشعار المالك بالبريد الإلكتروني.");

        return redirect()->route('admin.tenants.show', $record->slug);
    }

    public function updateMarketing(
        UpdateTenantMarketingRequest $request,
        string $tenant,
        ImageUploader $uploader,
        PlatformAuditor $auditor,
    ): RedirectResponse {
        $record = Tenant::query()->where('slug', $tenant)->firstOrFail();

        $record->update([
            'show_on_marketing' => $request->boolean('show_on_marketing'),
        ]);

        if ($request->boolean('remove_logo')) {
            $uploader->deleteCollection($record, 'marketing_logo');
            $uploader->deleteCollection($record, 'logo');
        }

        if ($request->hasFile('marketing_logo')) {
            $uploader->deleteCollection($record, 'marketing_logo');
            $uploader->store(
                $record,
                $request->file('marketing_logo'),
                'logo',
                $request->input('alt_text'),
            );
        }

        MarketingCache::flush();
        $auditor->log('tenant.marketing.updated', $record, [
            'show_on_marketing' => $record->show_on_marketing,
        ]);

        flash()->info('تم تحديث إعدادات التسويق للمستأجر بنجاح.');

        return redirect()->route('admin.tenants.show', $record->slug);
    }

    /**
     * The row shape both views consume. Kept as an array so the existing Blade
     * markup needed no rewrite when the data behind it became real.
     *
     * @return array<string, mixed>
     */
    private function summarize(Tenant $tenant): array
    {
        $owner = $this->ownerOf($tenant);

        return [
            'id' => $tenant->id,
            'name' => $tenant->name,
            'slug' => $tenant->slug,
            'owner' => $owner?->name ?? '—',
            'email' => $owner?->email ?? '—',
            'plan' => $tenant->currentPlan()?->name ?? '—',
            'plan_id' => $tenant->plan_id,
            'status' => $tenant->status->value,
            'employees' => $this->employeeCount($tenant),
            'signup' => $tenant->created_at?->toDateString() ?? '—',
            'last_active' => $this->lastActivity($tenant) ?? '—',
            'rejection_reason' => $tenant->rejection_reason,
        ];
    }

    private function ownerOf(Tenant $tenant): ?User
    {
        return User::query()
            ->where('tenant_id', $tenant->id)
            ->oldest('id')
            ->first();
    }

    private function employeeCount(Tenant $tenant): int
    {
        return Employee::query()->withoutGlobalScopes()
            ->where('tenant_id', $tenant->id)
            ->whereNull('deleted_at')
            ->count();
    }

    /**
     * Real last activity, taken from the tenant's audit log.
     *
     * Returns null rather than inventing a plausible "منذ ساعتين" when the
     * tenant has never done anything — a fabricated timestamp on a Super Admin
     * review screen is worse than an honest dash.
     */
    private function lastActivity(Tenant $tenant): ?string
    {
        $latest = DB::table('audit_logs')
            ->where('tenant_id', $tenant->id)
            ->max('created_at');

        return $latest !== null
            ? Carbon::parse($latest)->diffForHumans()
            : null;
    }

    /**
     * Capacity meters, read through {@see TenantPlanResolver} so this screen
     * and the tenant's own subscription page cannot disagree about the limit.
     *
     * @return list<array{label: string, used: int, limit: int}>
     */
    private function usage(Tenant $tenant, TenantPlanResolver $resolver, int $employees, int $departments): array
    {
        $rows = [
            ['key' => 'max_employees', 'label' => 'الموظفون', 'used' => $employees],
            ['key' => 'max_departments', 'label' => 'الأقسام', 'used' => $departments],
            ['key' => 'max_users', 'label' => 'مقاعد المستخدمين', 'used' => User::query()->where('tenant_id', $tenant->id)->count()],
        ];

        return collect($rows)->map(function (array $row) use ($resolver, $tenant): array {
            $limit = $resolver->limitFor($row['key'], $tenant);

            return [
                'label' => $row['label'],
                'used' => $row['used'],
                // The meter renders a bar, so an uncapped plan reports its own
                // usage as the denominator rather than a fake ceiling.
                'limit' => $limit === TenantPlanResolver::UNLIMITED ? max($row['used'], 1) : $limit,
            ];
        })->all();
    }

    /**
     * @return list<array{action: string, actor: string, time: string}>
     */
    private function recentAudit(Tenant $tenant): array
    {
        return DB::table('audit_logs')
            ->leftJoin('users', 'users.id', '=', 'audit_logs.user_id')
            ->where('audit_logs.tenant_id', $tenant->id)
            ->orderByDesc('audit_logs.created_at')
            ->limit(8)
            ->get(['audit_logs.action', 'audit_logs.created_at', 'users.name as actor'])
            ->map(fn ($row): array => [
                'action' => (string) $row->action,
                'actor' => (string) ($row->actor ?? 'النظام'),
                'time' => Carbon::parse($row->created_at)->diffForHumans(),
            ])
            ->all();
    }
}
