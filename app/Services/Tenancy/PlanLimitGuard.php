<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Tenant;
use App\Events\Tenancy\SubscriptionLimitApproaching;
use App\Events\Tenancy\SubscriptionLimitReached;
use App\Models\Plan;
use Illuminate\Support\Collection;

/**
 * Soft plan-limit checks for tenant resource creation (employees / departments).
 */
class PlanLimitGuard
{
    public const APPROACHING_PERCENT = 80.0;

    /**
     * @var array<string, array{max_employees: ?int, max_departments: ?int}>
     */
    private const DEFAULT_LIMITS = [
        'startup' => ['max_employees' => 10, 'max_departments' => 5],
        'growth' => ['max_employees' => 100, 'max_departments' => 20],
        'enterprise' => ['max_employees' => null, 'max_departments' => null],
    ];

    /**
     * @return array{allowed: bool, used: int, limit: ?int, percent: float, resource: string, label: string}
     */
    public function inspect(Tenant $tenant, string $resource): array
    {
        $limits = $this->resolveLimits($tenant);
        [$used, $limit, $label] = match ($resource) {
            'employees' => [Employee::query()->count(), $limits['max_employees'], 'الموظفون'],
            'departments' => [Department::query()->count(), $limits['max_departments'], 'الأقسام'],
            default => throw new \InvalidArgumentException("Unknown plan resource [{$resource}]"),
        };

        $percent = $limit === null || $limit <= 0
            ? 0.0
            : min(100, round(($used / $limit) * 100, 1));

        return [
            'allowed' => $limit === null || $used < $limit,
            'used' => $used,
            'limit' => $limit,
            'percent' => $percent,
            'resource' => $resource,
            'label' => $label,
        ];
    }

    /**
     * Block creation when the current count already meets/exceeds the plan limit.
     *
     * @return array{allowed: bool, used: int, limit: ?int, percent: float, resource: string, label: string}
     */
    public function assertCanCreate(Tenant $tenant, string $resource): array
    {
        $snapshot = $this->inspect($tenant, $resource);

        if (! $snapshot['allowed']) {
            event(new SubscriptionLimitReached(
                $tenant,
                $resource,
                $snapshot['used'],
                (int) $snapshot['limit'],
                $snapshot['label'],
            ));
        }

        return $snapshot;
    }

    /**
     * After a successful create, notify when usage crosses 80% or hits 100%.
     */
    public function evaluateAfterCreate(Tenant $tenant, string $resource): void
    {
        $snapshot = $this->inspect($tenant, $resource);

        if ($snapshot['limit'] === null) {
            return;
        }

        if ($snapshot['percent'] >= 100) {
            event(new SubscriptionLimitReached(
                $tenant,
                $resource,
                $snapshot['used'],
                (int) $snapshot['limit'],
                $snapshot['label'],
            ));

            return;
        }

        if ($snapshot['percent'] >= self::APPROACHING_PERCENT) {
            event(new SubscriptionLimitApproaching(
                $tenant,
                $resource,
                $snapshot['used'],
                (int) $snapshot['limit'],
                $snapshot['percent'],
                $snapshot['label'],
            ));
        }
    }

    /**
     * @return array{max_employees: ?int, max_departments: ?int}
     */
    private function resolveLimits(Tenant $tenant): array
    {
        $slug = (string) $tenant->plan;
        $defaults = self::DEFAULT_LIMITS[$slug] ?? self::DEFAULT_LIMITS['startup'];

        $plan = Plan::query()->with('features')->where('slug', $slug)->first();

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
        ];
    }

    private function nullableInt(mixed $value, ?int $fallback): ?int
    {
        if ($value === null || $value === '' || $value === 'unlimited' || $value === '-1') {
            return $value === 'unlimited' || $value === '-1' ? null : $fallback;
        }

        return (int) $value;
    }
}
