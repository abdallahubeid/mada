<?php

namespace App\Domain\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\Plan;
use App\Models\PlanFeature;
use Illuminate\Support\Facades\Cache;

/**
 * Reads a tenant's plan entitlements — capacity limits and feature flags.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * WHAT THIS IS AND IS NOT
 *
 * This resolver ANSWERS questions about entitlements. It does not enforce
 * them. Point-of-creation enforcement (`CheckFeatureLimit`) is Phase 4 scope
 * per DEVELOPMENT_ROADMAP.md, and adding blocking here would begin that phase
 * without the roadmap amendment ADR-11 requires.
 *
 * Callers therefore get `limitFor()` / `hasCapacityFor()` / `hasFeature()` and
 * decide for themselves. Today that means warnings and the subscription
 * dashboard; when Phase 4 lands, the middleware reads the same methods rather
 * than inventing a second entitlement source.
 *
 * A tenant with no plan is UNLIMITED, not zero. Plans are a commercial
 * construct layered on top of a working product — a tenant whose plan row was
 * deleted must keep operating, not have every creation screen refuse it.
 * ─────────────────────────────────────────────────────────────────────────
 */
class TenantPlanResolver
{
    /** Sentinel for an uncapped entitlement. */
    public const UNLIMITED = -1;

    private const CACHE_TTL_SECONDS = 300;

    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * The plan a tenant is entitled to, or null when none is assigned.
     *
     * Resolution order is `plan_id` first, then the legacy `plan` slug — the
     * slug is a denormalised cache and the FK is the source of truth, so a
     * disagreement resolves to the FK rather than to whichever was read first.
     */
    public function planFor(?Tenant $tenant = null): ?Plan
    {
        $tenant = $tenant ?? $this->tenantContext->getTenant();

        if ($tenant === null) {
            return null;
        }

        if ($tenant->plan_id !== null) {
            return Plan::query()->find($tenant->plan_id);
        }

        return filled($tenant->plan)
            ? Plan::query()->where('slug', $tenant->plan)->first()
            : null;
    }

    /**
     * A numeric capacity limit, e.g. `max_employees`.
     *
     * Returns {@see self::UNLIMITED} when the plan says "unlimited", when the
     * key is not defined on the plan, or when the tenant has no plan at all.
     */
    public function limitFor(string $featureKey, ?Tenant $tenant = null): int
    {
        $value = $this->rawValue($featureKey, $tenant);

        if ($value === null || $value === '' || strtolower($value) === 'unlimited') {
            return self::UNLIMITED;
        }

        return is_numeric($value) ? (int) $value : self::UNLIMITED;
    }

    /**
     * Whether $currentCount is below the limit for $featureKey.
     *
     * Takes the CURRENT count rather than counting internally: the caller
     * already knows which scope it means (active employees vs all rows,
     * including trashed or not), and guessing that here would silently apply
     * the wrong denominator.
     */
    public function hasCapacityFor(string $featureKey, int $currentCount, ?Tenant $tenant = null): bool
    {
        $limit = $this->limitFor($featureKey, $tenant);

        return $limit === self::UNLIMITED || $currentCount < $limit;
    }

    public function remainingCapacity(string $featureKey, int $currentCount, ?Tenant $tenant = null): ?int
    {
        $limit = $this->limitFor($featureKey, $tenant);

        return $limit === self::UNLIMITED ? null : max(0, $limit - $currentCount);
    }

    /**
     * Whether a non-numeric feature flag is switched on for this tenant.
     *
     * Absent means NOT granted — the inverse of the capacity default. A missing
     * capacity row means "we never capped this"; a missing feature row means
     * "this plan does not include it".
     */
    public function hasFeature(string $featureKey, ?Tenant $tenant = null): bool
    {
        $value = $this->rawValue($featureKey, $tenant);

        if ($value === null) {
            return false;
        }

        return ! in_array(strtolower($value), ['0', 'false', 'off', 'no', ''], true);
    }

    /**
     * Every keyed entitlement on the tenant's plan.
     *
     * @return array<string, string>
     */
    public function entitlements(?Tenant $tenant = null): array
    {
        $plan = $this->planFor($tenant);

        if ($plan === null) {
            return [];
        }

        return Cache::remember(
            "tenant:plan:{$plan->id}:entitlements",
            self::CACHE_TTL_SECONDS,
            fn (): array => PlanFeature::query()
                ->where('plan_id', $plan->id)
                ->whereNotNull('feature_key')
                ->pluck('value', 'feature_key')
                ->map(fn ($value): string => (string) $value)
                ->all(),
        );
    }

    /**
     * Drop the cached entitlements for a plan after the Super Admin edits it.
     */
    public function forget(int $planId): void
    {
        Cache::forget("tenant:plan:{$planId}:entitlements");
    }

    private function rawValue(string $featureKey, ?Tenant $tenant): ?string
    {
        return $this->entitlements($tenant)[$featureKey] ?? null;
    }
}
