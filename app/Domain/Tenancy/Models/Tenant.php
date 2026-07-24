<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Middleware\EnsureTenantActive;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Models\Concerns\HasImages;
use App\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A tenant represents a single customer organization on the Veyra platform.
 *
 * Tenants are the root of data isolation: every tenant-scoped model carries
 * a `tenant_id` column enforced via the {@see TenantScope}
 * global scope (applied through the {@see BelongsToTenant}
 * trait). The Tenant model itself is intentionally *not* tenant-scoped — it is
 * the thing being scoped. See docs/ARCHITECTURE.md §1 and §3 for the full
 * multi-tenancy model and the 5-state lifecycle enforced via `status`.
 */
class Tenant extends Model
{
    /** @use HasFactory<TenantFactory> */
    use HasFactory, HasImages, SoftDeletes;

    /**
     * Laravel's default factory name guesser assumes models live directly
     * under `App\Models`, which would resolve this to the non-existent
     * `Database\Factories\Domain\Tenancy\Models\TenantFactory`. Since Tenant
     * lives under the Domain-oriented structure (docs/VEYRA_DOCS.md §13),
     * the factory is wired explicitly instead.
     */
    protected static function newFactory(): TenantFactory
    {
        return TenantFactory::new();
    }

    /**
     * @var list<string>
     */
    protected $fillable = [
        'name',
        'slug',
        'status',
        'industry',
        'team_size',
        'plan',
        'show_on_marketing',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TenantStatus::PendingVerification->value,
        'show_on_marketing' => false,
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'show_on_marketing' => 'boolean',
        ];
    }

    /**
     * The users belonging to this tenant — the Owner/CEO and its employees.
     *
     * @return HasMany<User, $this>
     */
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * Whether the tenant has completed onboarding and is allowed to use
     * operational features (HR, Projects, Finance). Mirrors the check
     * performed by {@see EnsureTenantActive}.
     */
    public function isActive(): bool
    {
        return $this->status === TenantStatus::Active;
    }
}
