<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Spatie\Permission\Traits\HasRoles;

/**
 * Implements {@see MustVerifyEmailContract} so the SaaS registration flow
 * (docs/USER_JOURNEYS.md — Onboarding) can gate access behind a signed
 * email-verification link before a tenant reaches `pending_approval`.
 */
#[Fillable(['tenant_id', 'name', 'email', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasRoles, MustVerifyEmail, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
        ];
    }

    /**
     * The tenant this user belongs to.
     *
     * Nullable: Super Admin users have no tenant (`tenant_id = null`).
     * Unlike tenant-owned business data, `User` deliberately does not use
     * {@see BelongsToTenant} — resolving which
     * tenant a user belongs to is what bootstraps {@see TenantContext}
     * in the first place (see docs/ARCHITECTURE.md §1.2), so this relation
     * cannot depend on the very context it helps establish.
     *
     * @return BelongsTo<Tenant, $this>
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(Tenant::class);
    }
}
