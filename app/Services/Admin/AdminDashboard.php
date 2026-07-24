<?php

namespace App\Services\Admin;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Models\Faq;
use App\Models\Plan;
use App\Models\PlatformAuditLog;
use App\Models\Testimonial;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

/**
 * Assembles Super Admin dashboard payloads from live platform tables
 * (docs/MODULES.md §6). Aggregate counts use a short TTL cache.
 */
class AdminDashboard
{
    public const CACHE_TTL_SECONDS = 60;

    /**
     * @return array{
     *     range: string,
     *     metrics: array<string, array{label: string, value: string, delta: string|null, trend: string}>,
     *     approvalQueue: list<array{company: string, slug: string, owner: string, email: string, plan: string, waiting: string}>,
     *     distribution: list<array{key: string, label: string, count: int, color: string}>,
     *     planBreakdown: list<array{slug: string, name: string, count: int, percent: float}>,
     *     recentSignups: list<array{name: string, slug: string, plan: string, status: string, created: string}>,
     *     activity: list<array{type: string, actor: string, action: string, target: string, time: string}>,
     *     systemStatus: list<array{label: string, value: string, ok: bool, hint: string|null}>
     * }
     */
    public function build(string $range = '30d'): array
    {
        $range = $this->normalizeRange($range);
        [$from, $to, $prevFrom] = $this->periodBounds($range);

        return [
            'range' => $range,
            'metrics' => Cache::remember(
                "admin.dashboard.metrics.{$range}",
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->metrics($from, $to, $prevFrom),
            ),
            'distribution' => Cache::remember(
                'admin.dashboard.distribution',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->distribution(),
            ),
            'planBreakdown' => Cache::remember(
                'admin.dashboard.plan_breakdown',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->planBreakdown(),
            ),
            'systemStatus' => Cache::remember(
                'admin.dashboard.system_status',
                self::CACHE_TTL_SECONDS,
                fn (): array => $this->systemStatus(),
            ),
            'approvalQueue' => $this->approvalQueue(),
            'recentSignups' => $this->recentSignups(),
            'activity' => $this->activity(),
        ];
    }

    public static function flush(): void
    {
        foreach (['today', '7d', '30d'] as $range) {
            Cache::forget("admin.dashboard.metrics.{$range}");
        }

        Cache::forget('admin.dashboard.distribution');
        Cache::forget('admin.dashboard.plan_breakdown');
        Cache::forget('admin.dashboard.system_status');
    }

    /**
     * @return array<string, array{label: string, value: string, delta: string|null, trend: string}>
     */
    private function metrics(CarbonInterface $from, CarbonInterface $to, CarbonInterface $prevFrom): array
    {
        $total = Tenant::query()->count();
        $active = Tenant::query()->where('status', TenantStatus::Active)->count();
        $pending = Tenant::query()->where('status', TenantStatus::PendingApproval)->count();
        $suspended = Tenant::query()->where('status', TenantStatus::Suspended)->count();

        $signupsCurrent = Tenant::query()->whereBetween('created_at', [$from, $to])->count();
        $signupsPrevious = Tenant::query()->whereBetween('created_at', [$prevFrom, $from])->count();

        $mrr = (float) Tenant::query()
            ->where('tenants.status', TenantStatus::Active)
            ->join('plans', 'plans.slug', '=', 'tenants.plan')
            ->whereNull('plans.deleted_at')
            ->sum('plans.price_monthly');

        return [
            'total' => [
                'label' => 'إجمالي المستأجرين',
                'value' => $this->formatCount($total),
                ...$this->percentDelta($signupsCurrent, $signupsPrevious),
            ],
            'active' => [
                'label' => 'المستأجرون النشطون',
                'value' => $this->formatCount($active),
                ...$this->absoluteStatusDelta(TenantStatus::Active, $from, $to, $prevFrom),
            ],
            'pending' => [
                'label' => 'بانتظار الموافقة',
                'value' => $this->formatCount($pending),
                ...$this->absoluteStatusDelta(TenantStatus::PendingApproval, $from, $to, $prevFrom),
            ],
            'suspended' => [
                'label' => 'موقوفون',
                'value' => $this->formatCount($suspended),
                ...$this->absoluteStatusDelta(TenantStatus::Suspended, $from, $to, $prevFrom),
            ],
            'mrr' => [
                'label' => 'الإيراد الشهري التقديري',
                'value' => '$'.$this->formatCount((int) round($mrr)),
                'delta' => null,
                'trend' => 'up',
            ],
        ];
    }

    /**
     * @return list<array{key: string, label: string, count: int, color: string}>
     */
    private function distribution(): array
    {
        /** @var Collection<string, int> $counts */
        $counts = Tenant::query()
            ->selectRaw('status, COUNT(*) as aggregate')
            ->groupBy('status')
            ->pluck('aggregate', 'status')
            ->map(fn ($count): int => (int) $count);

        $segments = [
            ['key' => TenantStatus::Active->value, 'label' => 'نشط', 'color' => '#4edea3'],
            ['key' => TenantStatus::PendingApproval->value, 'label' => 'بانتظار الموافقة', 'color' => '#f59e0b'],
            ['key' => TenantStatus::PendingVerification->value, 'label' => 'بانتظار التحقق', 'color' => '#38bdf8'],
            ['key' => TenantStatus::Suspended->value, 'label' => 'موقوف', 'color' => '#fc7c78'],
            ['key' => TenantStatus::Cancelled->value, 'label' => 'ملغى', 'color' => '#9db0a4'],
        ];

        return array_map(fn (array $segment): array => [
            ...$segment,
            'count' => $counts->get($segment['key'], 0),
        ], $segments);
    }

    /**
     * @return list<array{slug: string, name: string, count: int, percent: float}>
     */
    private function planBreakdown(): array
    {
        /** @var Collection<string, int> $counts */
        $counts = Tenant::query()
            ->selectRaw('plan, COUNT(*) as aggregate')
            ->groupBy('plan')
            ->pluck('aggregate', 'plan')
            ->map(fn ($count): int => (int) $count);

        $total = max(1, $counts->sum());
        $plans = Plan::query()->orderBy('sort_order')->get(['slug', 'name']);

        $rows = $plans->map(function (Plan $plan) use ($counts, $total): array {
            $count = $counts->get($plan->slug, 0);

            return [
                'slug' => $plan->slug,
                'name' => $plan->name,
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        })->all();

        $known = $plans->pluck('slug')->all();
        foreach ($counts as $slug => $count) {
            if (in_array($slug, $known, true)) {
                continue;
            }

            $rows[] = [
                'slug' => (string) $slug,
                'name' => Str::headline((string) $slug),
                'count' => $count,
                'percent' => round(($count / $total) * 100, 1),
            ];
        }

        return $rows;
    }

    /**
     * @return list<array{company: string, slug: string, owner: string, email: string, plan: string, waiting: string}>
     */
    private function approvalQueue(): array
    {
        return Tenant::query()
            ->with('users')
            ->where('status', TenantStatus::PendingApproval)
            ->latest('created_at')
            ->limit(8)
            ->get(['id', 'name', 'slug', 'plan', 'created_at'])
            ->map(function (Tenant $tenant): array {
                $owner = $tenant->users->sortBy('id')->first();

                return [
                    'company' => $tenant->name,
                    'slug' => $tenant->slug,
                    'owner' => $owner?->name ?? '—',
                    'email' => $owner?->email ?? '—',
                    'plan' => Str::headline((string) $tenant->plan),
                    'waiting' => $tenant->created_at?->diffForHumans() ?? '—',
                ];
            })
            ->all();
    }

    /**
     * @return list<array{name: string, slug: string, plan: string, status: string, created: string}>
     */
    private function recentSignups(): array
    {
        return Tenant::query()
            ->latest('created_at')
            ->limit(8)
            ->get(['name', 'slug', 'plan', 'status', 'created_at'])
            ->map(fn (Tenant $tenant): array => [
                'name' => $tenant->name,
                'slug' => $tenant->slug,
                'plan' => Str::headline((string) $tenant->plan),
                'status' => $tenant->status instanceof TenantStatus
                    ? $tenant->status->value
                    : (string) $tenant->status,
                'created' => $tenant->created_at?->diffForHumans() ?? '—',
            ])
            ->all();
    }

    /**
     * @return list<array{type: string, actor: string, action: string, target: string, time: string}>
     */
    private function activity(): array
    {
        $logs = PlatformAuditLog::query()
            ->with('user:id,name')
            ->latest('id')
            ->limit(10)
            ->get();

        if ($logs->isNotEmpty()) {
            return $logs->map(fn (PlatformAuditLog $log): array => [
                'type' => $this->activityType($log->action),
                'actor' => $log->user?->name ?? 'النظام',
                'action' => $this->activityLabel($log->action),
                'target' => $this->activityTarget($log),
                'time' => $log->created_at?->diffForHumans() ?? '—',
            ])->all();
        }

        return Tenant::query()
            ->latest('created_at')
            ->limit(5)
            ->get(['name', 'created_at'])
            ->map(fn (Tenant $tenant): array => [
                'type' => 'signup',
                'actor' => 'النظام',
                'action' => 'تسجيل مستأجر جديد',
                'target' => $tenant->name,
                'time' => $tenant->created_at?->diffForHumans() ?? '—',
            ])
            ->all();
    }

    /**
     * @return list<array{label: string, value: string, ok: bool, hint: string|null}>
     */
    private function systemStatus(): array
    {
        $pending = Tenant::query()->where('status', TenantStatus::PendingApproval)->count();
        $activePlans = Plan::query()->active()->count();
        $publishedFaqs = Faq::query()->published()->count();
        $publishedTestimonials = Testimonial::query()->published()->count();
        $platformUsers = User::query()->whereNull('tenant_id')->count();
        $tenantUsers = User::query()->whereNotNull('tenant_id')->count();

        return [
            [
                'label' => 'الخطط النشطة',
                'value' => $this->formatCount($activePlans),
                'ok' => $activePlans > 0,
                'hint' => $activePlans > 0 ? null : 'لا توجد خطط منشورة',
            ],
            [
                'label' => 'الأسئلة المنشورة',
                'value' => $this->formatCount($publishedFaqs),
                'ok' => true,
                'hint' => null,
            ],
            [
                'label' => 'الشهادات المنشورة',
                'value' => $this->formatCount($publishedTestimonials),
                'ok' => true,
                'hint' => null,
            ],
            [
                'label' => 'مستخدمو المنصّة',
                'value' => $this->formatCount($platformUsers),
                'ok' => true,
                'hint' => null,
            ],
            [
                'label' => 'مستخدمو المستأجرين',
                'value' => $this->formatCount($tenantUsers),
                'ok' => true,
                'hint' => null,
            ],
            [
                'label' => 'طابور الموافقات',
                'value' => $this->formatCount($pending),
                'ok' => $pending === 0,
                'hint' => $pending > 0 ? 'يحتاج مراجعة' : 'لا يوجد معلق',
            ],
        ];
    }

    private function formatCount(int|float $value): string
    {
        return number_format((float) $value, 0, '.', ',');
    }

    /**
     * @return array{0: CarbonInterface, 1: CarbonInterface, 2: CarbonInterface}
     */
    private function periodBounds(string $range): array
    {
        $to = now();

        $from = match ($range) {
            'today' => now()->startOfDay(),
            '7d' => now()->subDays(7),
            default => now()->subDays(30),
        };

        $length = max(1, $from->diffInSeconds($to));
        $prevFrom = Carbon::instance($from)->subSeconds($length);

        return [$from, $to, $prevFrom];
    }

    private function normalizeRange(string $range): string
    {
        return in_array($range, ['today', '7d', '30d'], true) ? $range : '30d';
    }

    /**
     * @return array{delta: string, trend: string}
     */
    private function percentDelta(int $current, int $previous): array
    {
        if ($previous === 0) {
            $pct = $current > 0 ? 100.0 : 0.0;
        } else {
            $pct = (($current - $previous) / $previous) * 100;
        }

        $rounded = round($pct, 1);

        return [
            'delta' => ($rounded >= 0 ? '+' : '').$rounded.'%',
            'trend' => $rounded < 0 ? 'down' : 'up',
        ];
    }

    /**
     * @return array{delta: string, trend: string}
     */
    private function absoluteStatusDelta(
        TenantStatus $status,
        CarbonInterface $from,
        CarbonInterface $to,
        CarbonInterface $prevFrom,
    ): array {
        $current = Tenant::query()
            ->where('status', $status)
            ->whereBetween('created_at', [$from, $to])
            ->count();

        $previous = Tenant::query()
            ->where('status', $status)
            ->whereBetween('created_at', [$prevFrom, $from])
            ->count();

        $diff = $current - $previous;

        return [
            'delta' => ($diff >= 0 ? '+' : '').$diff,
            'trend' => $diff < 0 ? 'down' : 'up',
        ];
    }

    private function activityType(string $action): string
    {
        return match (true) {
            str_contains($action, 'tenant') && str_contains($action, 'marketing') => 'signup',
            str_contains($action, 'plan') => 'approval',
            str_contains($action, 'faq'), str_contains($action, 'testimonial'), str_contains($action, 'settings') => 'security',
            str_contains($action, 'suspend') => 'suspension',
            default => 'approval',
        };
    }

    private function activityLabel(string $action): string
    {
        return match ($action) {
            'plan.created' => 'أنشأ خطة',
            'plan.updated' => 'حدّث خطة',
            'plan.archived' => 'أرشف خطة',
            'faq.created' => 'أضاف سؤالاً شائعًا',
            'faq.updated' => 'حدّث سؤالاً شائعًا',
            'faq.deleted' => 'حذف سؤالاً شائعًا',
            'testimonial.created' => 'أضاف شهادة',
            'testimonial.updated' => 'حدّث شهادة',
            'testimonial.deleted' => 'حذف شهادة',
            'tenant.marketing.updated' => 'حدّث تسويق مستأجر',
            default => str_replace('.', ' · ', $action),
        };
    }

    private function activityTarget(PlatformAuditLog $log): string
    {
        if (is_array($log->meta) && isset($log->meta['keys'])) {
            return implode(', ', $log->meta['keys']);
        }

        if ($log->subject_type && $log->subject_id) {
            return class_basename($log->subject_type).' #'.$log->subject_id;
        }

        return 'المنصّة';
    }
}
