<?php

namespace App\Domain\Tenancy\Models;

use App\Domain\Tenancy\Actions\ApproveTenantAction;
use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Enums\BillingCycle;
use App\Domain\Tenancy\Enums\SubscriptionStatus;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Middleware\EnsureTenantActive;
use App\Domain\Tenancy\Scopes\TenantScope;
use App\Models\Concerns\HasImages;
use App\Models\Plan;
use App\Models\User;
use Database\Factories\TenantFactory;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * A tenant represents a single customer organization on the Mada platform.
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
     * lives under the Domain-oriented structure (docs/MADA_DOCS.md §13),
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
        'plan_id',
        'billing_cycle',
        'subscription_status',
        'trial_ends_at',
        'renews_at',
        'activated_at',
        'reviewed_at',
        'reviewed_by',
        'rejection_reason',
        'suspended_at',
        'suspension_reason',
        'suspended_by',
        'show_on_marketing',
    ];

    /**
     * @var array<string, mixed>
     */
    protected $attributes = [
        'status' => TenantStatus::PendingVerification->value,
        'show_on_marketing' => false,
        'billing_cycle' => 'monthly',
        'subscription_status' => 'trial',
    ];

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => TenantStatus::class,
            'show_on_marketing' => 'boolean',
            'billing_cycle' => BillingCycle::class,
            'subscription_status' => SubscriptionStatus::class,
            'trial_ends_at' => 'datetime',
            'renews_at' => 'datetime',
            'activated_at' => 'datetime',
            'reviewed_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    /**
     * Tenants are addressed by slug in every URL that carries one.
     *
     * The Platform Console resolves `/admin/tenants/{tenant}` with
     * `where('slug', ...)`, so without this override `route('admin.tenants.show',
     * $tenant)` generated `/admin/tenants/12` from the primary key and every
     * such link 404'd. That hit the pending-approval notification, the global
     * search results and anything else that passed the model rather than
     * `$tenant->slug` — fixing the key here fixes all of them at once, instead
     * of leaving each call site one forgotten `->slug` away from the same bug.
     */
    public function getRouteKeyName(): string
    {
        return 'slug';
    }

    /**
     * The pricing plan this tenant was approved onto.
     *
     * `plan_id` is the source of truth; the legacy `plan` slug column is kept
     * in sync by {@see ApproveTenantAction} purely
     * as a denormalised read cache for SubscriptionOverview.
     *
     * @return BelongsTo<Plan, $this>
     */
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class, 'plan_id');
    }

    /**
     * The Super Admin who approved or rejected this registration.
     *
     * @return BelongsTo<User, $this>
     */
    public function reviewer(): BelongsTo
    {
        return $this->belongsTo(User::class, 'reviewed_by');
    }

    /**
     * The Super Admin who suspended this tenant, while the suspension stands.
     *
     * Null once reactivated — the columns describe the current suspension only.
     * The full history is in `platform_audit_logs`.
     *
     * @return BelongsTo<User, $this>
     */
    public function suspender(): BelongsTo
    {
        return $this->belongsTo(User::class, 'suspended_by');
    }

    /**
     * Awaiting a Super Admin decision — the only state the approval screen acts on.
     *
     * @param  Builder<$this>  $query
     */
    public function scopeAwaitingReview(Builder $query): void
    {
        $query->where('status', TenantStatus::PendingApproval->value);
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
     * @return HasOne<OrgSetting, $this>
     */
    public function orgSetting(): HasOne
    {
        return $this->hasOne(OrgSetting::class);
    }

    /**
     * @return HasMany<WorkCalendar, $this>
     */
    public function workCalendars(): HasMany
    {
        return $this->hasMany(WorkCalendar::class);
    }

    /**
     * @return HasOne<TenantPortalSetting, $this>
     */
    public function portalSetting(): HasOne
    {
        return $this->hasOne(TenantPortalSetting::class);
    }

    /**
     * @return HasMany<TenantInvoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(TenantInvoice::class);
    }

    /**
     * The plan row this tenant runs on.
     *
     * Prefers the `plan_id` FK and falls back to the legacy `tenants.plan`
     * slug. The order matters: the FK is the source of truth, so when the two
     * disagree — a slug left stale by an older write path — the FK wins rather
     * than whichever the reader happened to consult.
     */
    public function currentPlan(): ?Plan
    {
        if ($this->plan_id !== null) {
            return Plan::query()->find($this->plan_id);
        }

        if (! filled($this->plan)) {
            return null;
        }

        return Plan::query()->where('slug', $this->plan)->first();
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
