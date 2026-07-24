<?php

namespace App\Domain\Tenancy\Scopes;

use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Scope;

/**
 * Restricts every query on a tenant-scoped model to the currently resolved tenant.
 *
 * This is the mandatory "Data layer" enforcement point required by
 * docs/ARCHITECTURE.md §1.2 — no tenant-scoped model may be queried without
 * this scope applying automatically.
 *
 * When no tenant is resolved in the current context (a console command, a
 * queued job that hasn't re-bound its tenant, or a Super Admin request), the
 * scope intentionally adds no constraint. Cross-tenant access in that
 * situation must go through an explicit, audited query rather than relying
 * on this scope to silently "unlock" — see docs/ARCHITECTURE.md §1.2 point 2.
 */
class TenantScope implements Scope
{
    public function apply(Builder $builder, Model $model): void
    {
        $tenantId = app(TenantContext::class)->getTenantId();

        if ($tenantId !== null) {
            $builder->where($model->qualifyColumn('tenant_id'), $tenantId);
        }
    }
}
