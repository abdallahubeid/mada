<?php

namespace App\Domain\Tenancy\Concerns;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Marks an Eloquent model as tenant-owned data.
 *
 * Use this trait on every model that stores tenant-scoped business data
 * (Employees, Departments, Projects, Tasks, Payroll runs, etc.) per
 * docs/ARCHITECTURE.md §1.2. It does two things:
 *
 *   1. Applies {@see TenantScope} globally, so every query is automatically
 *      filtered to the currently resolved tenant.
 *   2. Stamps `tenant_id` onto new records from the resolved tenant context,
 *      so callers never need to set it manually.
 *
 * The model using this trait must have a `tenant_id` column.
 */
trait BelongsToTenant
{
    public static function bootBelongsToTenant(): void
    {
        static::addGlobalScope(new TenantScope);

        static::creating(function ($model): void {
            if ($model->tenant_id === null) {
                $model->tenant_id = app(TenantContext::class)->getTenantId();
            }
        });
    }

    /**
     * The tenant this record belongs to.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
