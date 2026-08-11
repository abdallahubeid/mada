<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Enums\BillingCycle;
use App\Domain\Tenancy\Enums\SubscriptionStatus;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvoice;
use App\Models\Image;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Carbon;
use Illuminate\Support\Collection;

/**
 * Builds the tenant subscription dashboard payload (plan, usage meters, invoices).
 */
class SubscriptionOverview
{
    /**
     * Default quota limits by plan slug when plan_features values are absent.
     *
     * @var array<string, array{max_employees: ?int, max_departments: ?int, max_storage_mb: ?int}>
     */
    private const DEFAULT_LIMITS = [
        'startup' => [
            'max_employees' => 10,
            'max_departments' => 5,
            'max_storage_mb' => 1024,
        ],
        'growth' => [
            'max_employees' => 100,
            'max_departments' => 20,
            'max_storage_mb' => 10240,
        ],
        'enterprise' => [
            'max_employees' => null,
            'max_departments' => null,
            'max_storage_mb' => null,
        ],
    ];

    /**
     * @return array{
     *     tenant: Tenant,
     *     plan: ?Plan,
     *     planName: string,
     *     status: SubscriptionStatus,
     *     billingCycle: BillingCycle,
     *     price: ?string,
     *     currency: string,
     *     renewsAt: ?Carbon,
     *     trialEndsAt: ?Carbon,
     *     daysUntilRenewal: ?int,
     *     renewalWarning: bool,
     *     usage: array<string, array{label: string, used: float|int, limit: ?int, unit: string, percent: float}>,
     *     invoices: LengthAwarePaginator
     * }
     */
    public function for(Tenant $tenant): array
    {
        $plan = Plan::query()
            ->with('features')
            ->where('slug', $tenant->plan)
            ->first();

        $billingCycle = $tenant->billing_cycle instanceof BillingCycle
            ? $tenant->billing_cycle
            : BillingCycle::tryFrom((string) $tenant->billing_cycle) ?? BillingCycle::Monthly;

        $status = $this->resolveStatus($tenant);
        $limits = $this->resolveLimits($plan, (string) $tenant->plan);

        $employeesUsed = Employee::query()->count();
        $departmentsUsed = Department::query()->count();
        $storageUsedMb = round($this->storageUsedBytes($tenant) / 1024 / 1024, 2);

        $renewsAt = $tenant->renews_at;
        $daysUntilRenewal = $renewsAt !== null
            ? (int) now()->startOfDay()->diffInDays($renewsAt->copy()->startOfDay(), false)
            : null;

        $price = $billingCycle === BillingCycle::Yearly
            ? $plan?->price_yearly
            : $plan?->price_monthly;

        $invoices = TenantInvoice::query()
            ->latest('issued_at')
            ->paginate(config('app.paginate_page'));

        return [
            'tenant' => $tenant,
            'plan' => $plan,
            'planName' => $plan?->name ?? (string) $tenant->plan,
            'status' => $status,
            'billingCycle' => $billingCycle,
            'price' => $price,
            'currency' => $plan?->currency ?? 'USD',
            'renewsAt' => $renewsAt,
            'trialEndsAt' => $tenant->trial_ends_at,
            'daysUntilRenewal' => $daysUntilRenewal,
            'renewalWarning' => $daysUntilRenewal !== null && $daysUntilRenewal >= 0 && $daysUntilRenewal < 7,
            'usage' => [
                'employees' => $this->meter('الموظفون', $employeesUsed, $limits['max_employees'], 'موظف'),
                'departments' => $this->meter('الأقسام', $departmentsUsed, $limits['max_departments'], 'قسم'),
                'storage' => $this->meter('التخزين', $storageUsedMb, $limits['max_storage_mb'], 'ميجابايت'),
            ],
            'invoices' => $invoices,
        ];
    }

    private function resolveStatus(Tenant $tenant): SubscriptionStatus
    {
        $status = $tenant->subscription_status instanceof SubscriptionStatus
            ? $tenant->subscription_status
            : SubscriptionStatus::tryFrom((string) $tenant->subscription_status) ?? SubscriptionStatus::Trial;

        if ($tenant->renews_at !== null && $tenant->renews_at->isPast()) {
            return SubscriptionStatus::Expired;
        }

        if (
            $status === SubscriptionStatus::Trial
            && $tenant->trial_ends_at !== null
            && $tenant->trial_ends_at->isPast()
        ) {
            return SubscriptionStatus::Expired;
        }

        return $status;
    }

    /**
     * @return array{max_employees: ?int, max_departments: ?int, max_storage_mb: ?int}
     */
    private function resolveLimits(?Plan $plan, string $slug): array
    {
        $defaults = self::DEFAULT_LIMITS[$slug] ?? self::DEFAULT_LIMITS['startup'];

        if ($plan === null) {
            return $defaults;
        }

        /** @var Collection<string, string|null> $values */
        $values = $plan->features
            ->filter(fn ($feature) => filled($feature->feature_key))
            ->mapWithKeys(fn ($feature) => [$feature->feature_key => $feature->value]);

        return [
            'max_employees' => $this->nullableInt($values->get('max_employees'), $defaults['max_employees']),
            'max_departments' => $this->nullableInt($values->get('max_departments'), $defaults['max_departments']),
            'max_storage_mb' => $this->nullableInt($values->get('max_storage_mb'), $defaults['max_storage_mb']),
        ];
    }

    private function nullableInt(mixed $value, ?int $fallback): ?int
    {
        if ($value === null || $value === '' || $value === 'unlimited' || $value === '-1') {
            return $value === 'unlimited' || $value === '-1' ? null : $fallback;
        }

        return (int) $value;
    }

    /**
     * @return array{label: string, used: float|int, limit: ?int, unit: string, percent: float}
     */
    private function meter(string $label, float|int $used, ?int $limit, string $unit): array
    {
        $percent = $limit === null || $limit <= 0
            ? 0.0
            : min(100, round(((float) $used / $limit) * 100, 1));

        return [
            'label' => $label,
            'used' => $used,
            'limit' => $limit,
            'unit' => $unit,
            'percent' => $percent,
        ];
    }

    private function storageUsedBytes(Tenant $tenant): int
    {
        $userIds = User::query()
            ->where('tenant_id', $tenant->id)
            ->pluck('id');

        if ($userIds->isEmpty()) {
            return 0;
        }

        return (int) Image::query()
            ->where('imageable_type', User::class)
            ->whereIn('imageable_id', $userIds)
            ->sum('file_size');
    }
}
