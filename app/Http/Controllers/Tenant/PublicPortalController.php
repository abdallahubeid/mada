<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Enums\JobPostingStatus;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantPortalSetting;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\JobApplicationSubmitted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StorePortalContactRequest;
use App\Http\Requests\Tenant\StorePublicJobApplicationRequest;
use App\Http\Requests\Tenant\UpdateTenantPortalSettingRequest;
use App\Services\Tenancy\TenantContactInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;

/**
 * Tenant Public Portal — owner settings + public careers site.
 *
 * Content is persisted on {@see TenantPortalSetting} (one row per tenant).
 * Public routes resolve the tenant from `{slug}` into {@see TenantContext}.
 * Published {@see JobPosting} records power /careers listings and applications.
 */
class PublicPortalController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function settings(): View
    {
        $tenant = $this->tenantContext->getTenant();
        abort_unless($tenant !== null, 403);

        $portalSettings = TenantPortalSetting::query()->first()
            ?? (new TenantPortalSetting)->forceFill(TenantPortalSetting::defaultAttributes($tenant));

        return view('tenant.settings.portal', [
            'tenant' => $tenant,
            'portalSettings' => $portalSettings,
            'previewUrl' => $tenant->slug
                ? route('portal.index', ['slug' => $tenant->slug])
                : null,
            'canUpdate' => auth()->user()?->can('tenant.settings.update') ?? false,
        ]);
    }

    public function updateSettings(UpdateTenantPortalSettingRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $user = $request->user();

        abort_unless($tenant !== null && $user !== null, 403);

        $data = $request->validated();

        DB::transaction(function () use ($data, $tenant, $user): void {
            $settings = TenantPortalSetting::query()->firstOrNew(['tenant_id' => $tenant->id]);

            if (! $settings->exists) {
                $settings->created_by = $user->id;
            }

            $settings->fill([
                ...$this->normalizedContent($data),
                'updated_by' => $user->id,
            ]);
            $settings->save();
        });

        flash()->info('تم تحديث إعدادات الموقع العام بنجاح.');

        return redirect()->route('settings.portal');
    }

    public function index(string $slug): View|Response
    {
        [$tenant, $portalSettings] = $this->resolvePublicPortal($slug);

        if (! $portalSettings->is_portal_enabled) {
            return $this->disabledResponse($tenant);
        }

        return view('tenant.portal.index', $this->portalViewData($tenant, $portalSettings));
    }

    public function careers(string $slug, Request $request): View|Response
    {
        [$tenant, $portalSettings] = $this->resolvePublicPortal($slug);

        if (! $portalSettings->is_portal_enabled) {
            return $this->disabledResponse($tenant);
        }

        abort_unless($portalSettings->isSectionActive('careers'), 404);

        $payload = $this->portalViewData($tenant, $portalSettings);
        $jobs = collect($payload['jobs']);

        if ($request->filled('q')) {
            $q = mb_strtolower((string) $request->string('q'));
            $jobs = $jobs->filter(fn (array $job): bool => str_contains(mb_strtolower($job['title']), $q)
                || str_contains(mb_strtolower($job['department']), $q));
        }

        if ($request->filled('department') && $request->string('department') !== 'all') {
            $department = (string) $request->string('department');
            $jobs = $jobs->filter(fn (array $job): bool => $job['department'] === $department);
        }

        $payload['jobs'] = $jobs->values()->all();
        $payload['filters'] = [
            'q' => (string) $request->string('q'),
            'department' => (string) $request->string('department', 'all'),
        ];

        return view('tenant.portal.careers', $payload);
    }

    public function jobDetail(string $slug, string $job): View|Response
    {
        [$tenant, $portalSettings] = $this->resolvePublicPortal($slug);

        if (! $portalSettings->is_portal_enabled) {
            return $this->disabledResponse($tenant);
        }

        abort_unless($portalSettings->isSectionActive('careers'), 404);

        $posting = $this->resolvePublishedJob($job);
        $payload = $this->portalViewData($tenant, $portalSettings);
        $payload['job'] = $posting->toPortalArray();
        $payload['jobPosting'] = $posting;

        return view('tenant.portal.job-detail', $payload);
    }

    public function applyForJob(StorePublicJobApplicationRequest $request, string $slug, string $job): RedirectResponse
    {
        [$tenant, $portalSettings] = $this->resolvePublicPortal($slug);

        abort_unless($portalSettings->is_portal_enabled, 404);
        abort_unless($portalSettings->isSectionActive('careers'), 404);

        $posting = $this->resolvePublishedJob($job);
        $data = $request->validated();

        $cvPath = $request->file('cv')->store(
            "tenant/{$tenant->id}/applications/cvs",
            'custom'
        );

        $application = JobApplication::query()->create([
            'tenant_id' => $tenant->id,
            'job_posting_id' => $posting->id,
            'applicant_name' => $data['applicant_name'],
            'email' => strtolower($data['email']),
            'phone' => $data['phone'],
            'cv_path' => $cvPath,
            'cover_letter' => $data['cover_letter'] ?? null,
        ]);

        event(new JobApplicationSubmitted($application));

        flash()->success('تم استلام طلب التقديم بنجاح. سنتواصل معك قريباً.');

        return redirect()->route('portal.jobs.show', [$slug, $posting->slug]);
    }

    public function contact(string $slug): View|Response
    {
        [$tenant, $portalSettings] = $this->resolvePublicPortal($slug);

        if (! $portalSettings->is_portal_enabled) {
            return $this->disabledResponse($tenant);
        }

        abort_unless($portalSettings->isSectionActive('contact'), 404);

        return view('tenant.portal.contact', $this->portalViewData($tenant, $portalSettings));
    }

    public function storeContact(StorePortalContactRequest $request, string $slug): RedirectResponse
    {
        [$tenant, $portalSettings] = $this->resolvePublicPortal($slug);

        abort_unless($portalSettings->is_portal_enabled, 404);
        abort_unless($portalSettings->isSectionActive('contact'), 404);

        $data = $request->validated();

        app(TenantContactInbox::class)->ingestPortalInquiry($tenant, [
            'name' => $data['name'],
            'email' => $data['email'],
            'subject' => $data['subject'],
            'message' => $data['message'],
        ]);

        flash()->success('شكرًا لتواصلك! تم إرسال رسالتك بنجاح وسنتواصل معك قريبًا.');

        return redirect()->route('portal.contact', $slug);
    }

    /**
     * @return array{0: Tenant, 1: TenantPortalSetting}
     */
    private function resolvePublicPortal(string $slug): array
    {
        $tenant = Tenant::query()
            ->where('slug', $slug)
            ->where('status', TenantStatus::Active)
            ->firstOrFail();

        $this->tenantContext->setTenant($tenant);

        return [$tenant, TenantPortalSetting::resolveForTenant($tenant)];
    }

    private function resolvePublishedJob(string $job): JobPosting
    {
        return JobPosting::query()
            ->with('department')
            ->where('status', JobPostingStatus::Published)
            ->where(function ($query) use ($job): void {
                $query->where('slug', $job);

                if (ctype_digit($job)) {
                    $query->orWhere('id', (int) $job);
                }
            })
            ->firstOrFail();
    }

    private function disabledResponse(Tenant $tenant): Response
    {
        return response()->view('tenant.portal.disabled', [
            'tenant' => $tenant,
            'company' => [
                'name' => $tenant->name,
                'logo_initial' => mb_substr($tenant->name, 0, 1),
            ],
        ], 404);
    }

    /**
     * @return array<string, mixed>
     */
    private function portalViewData(Tenant $tenant, TenantPortalSetting $portalSettings): array
    {
        $slug = $tenant->slug;
        $jobs = $this->publishedJobs();

        return [
            'slug' => $slug,
            'tenant' => $tenant,
            'portalSettings' => $portalSettings,
            'company' => [
                'name' => $tenant->name,
                'tagline' => $portalSettings->hero_subtitle
                    ?: 'نبني فرقاً استثنائية ونمنح المواهب مساحة للنمو.',
                'logo_initial' => mb_substr($tenant->name, 0, 1),
            ],
            'jobs' => $jobs,
            'departments' => collect($jobs)->pluck('department')->unique()->values()->all(),
            'contact' => [
                'email' => $portalSettings->contact_email,
                'phone' => $portalSettings->contact_phone,
                'address' => $portalSettings->contact_address,
                'hours' => $portalSettings->office_hours,
                'map_embed_url' => $portalSettings->map_embed_url
                    ?: 'https://maps.google.com/maps?q='.rawurlencode((string) $portalSettings->contact_address).'&z=14&output=embed',
            ],
            'heroPrimaryUrl' => $this->resolveCtaUrl(
                $portalSettings->hero_primary_cta_url,
                route('portal.careers', $slug),
                $slug,
            ),
            'heroSecondaryUrl' => $this->resolveCtaUrl(
                $portalSettings->hero_secondary_cta_url,
                route('portal.contact', $slug),
                $slug,
            ),
            'ctaButtonUrl' => route('portal.careers', $slug),
        ];
    }

    /**
     * @return list<array<string, mixed>>
     */
    private function publishedJobs(): array
    {
        return JobPosting::query()
            ->with('department')
            ->where('status', JobPostingStatus::Published)
            ->latest()
            ->get()
            ->map(fn (JobPosting $job): array => $job->toPortalArray())
            ->all();
    }

    private function resolveCtaUrl(?string $url, string $fallback, string $slug): string
    {
        if (blank($url)) {
            return $fallback;
        }

        if (str_starts_with($url, 'http://') || str_starts_with($url, 'https://') || str_starts_with($url, '/')) {
            return $url;
        }

        if ($url === 'careers') {
            return route('portal.careers', $slug);
        }

        if ($url === 'contact') {
            return route('portal.contact', $slug);
        }

        return $fallback;
    }

    /**
     * @param  array<string, mixed>  $data
     * @return array<string, mixed>
     */
    private function normalizedContent(array $data): array
    {
        $data['values_json'] = collect($data['values_json'] ?? [])
            ->filter(fn (array $row): bool => filled($row['title'] ?? null))
            ->map(fn (array $row): array => [
                'title' => (string) $row['title'],
                'desc' => (string) ($row['desc'] ?? ''),
            ])
            ->values()
            ->all();

        $data['services_json'] = collect($data['services_json'] ?? [])
            ->filter(fn (array $row): bool => filled($row['title'] ?? null))
            ->map(fn (array $row): array => [
                'title' => (string) $row['title'],
                'description' => (string) ($row['description'] ?? ''),
                'icon' => (string) ($row['icon'] ?? 'ops'),
            ])
            ->values()
            ->all();

        $data['culture_perks_json'] = collect($data['culture_perks_json'] ?? [])
            ->filter(fn (array $row): bool => filled($row['title'] ?? null))
            ->map(fn (array $row): array => [
                'title' => (string) $row['title'],
                'description' => (string) ($row['description'] ?? ''),
            ])
            ->values()
            ->all();

        $data['stats_json'] = collect($data['stats_json'] ?? [])
            ->filter(fn (array $row): bool => filled($row['label'] ?? null))
            ->map(fn (array $row): array => [
                'label' => (string) $row['label'],
                'value' => is_numeric($row['value'] ?? null) ? (int) $row['value'] : ($row['value'] ?? 0),
                'suffix' => (string) ($row['suffix'] ?? ''),
            ])
            ->values()
            ->all();

        $data['faqs_json'] = collect($data['faqs_json'] ?? [])
            ->filter(fn (array $row): bool => filled($row['question'] ?? null))
            ->map(fn (array $row): array => [
                'question' => (string) $row['question'],
                'answer' => (string) ($row['answer'] ?? ''),
            ])
            ->values()
            ->all();

        return $data;
    }
}
