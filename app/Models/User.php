<?php

namespace App\Models;

use App\Domain\Tenancy\Concerns\BelongsToTenant;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Models\Concerns\HasImages;
use Database\Factories\UserFactory;
use Illuminate\Auth\MustVerifyEmail;
use Illuminate\Contracts\Auth\MustVerifyEmail as MustVerifyEmailContract;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\Traits\HasRoles;

/**
 * Implements {@see MustVerifyEmailContract} so the SaaS registration flow
 * (docs/USER_JOURNEYS.md — Onboarding) can gate access behind a signed
 * email-verification link before a tenant reaches `pending_approval`.
 */
#[Fillable(['tenant_id', 'name', 'email', 'phone', 'job_title', 'password'])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable implements MustVerifyEmailContract
{
    /** @use HasFactory<UserFactory> */
    use HasFactory, HasImages, HasRoles, MustVerifyEmail, Notifiable, SoftDeletes;

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

    /**
     * Profile photo via the polymorphic images table (`collection = avatar`).
     *
     * @return MorphOne<Image, $this>
     */
    public function avatar(): MorphOne
    {
        return $this->morphOne(Image::class, 'imageable')->where('collection', 'avatar');
    }

    /**
     * Public URL for the user's avatar via the custom disk, or an initial SVG fallback.
     *
     * Uses the custom disk URL base (APP_URL, files rooted at public/) — same as
     * {@see Image::url()} / marketing uploads. Prefer this over a hard-coded /public prefix.
     */
    public function getAvatarUrlAttribute(): string
    {
        $image = $this->relationLoaded('avatar')
            ? $this->getRelation('avatar')
            : $this->avatar()->first();

        if ($image instanceof Image && filled($image->path)) {
            return Storage::disk('custom')->url($image->path);
        }

        return $this->initialAvatarDataUri();
    }

    /**
     * SVG data URI showing the user's first initial (fallback when no upload).
     */
    public function initialAvatarDataUri(): string
    {
        $initial = htmlspecialchars(mb_strtoupper(mb_substr(trim((string) $this->name), 0, 1) ?: '?'), ENT_QUOTES | ENT_XML1, 'UTF-8');
        $svg = <<<SVG
<svg xmlns="http://www.w3.org/2000/svg" viewBox="0 0 64 64" role="img" aria-label="{$initial}">
  <rect width="64" height="64" rx="32" fill="#10b981"/>
  <text x="32" y="38" text-anchor="middle" font-family="system-ui,sans-serif" font-size="28" font-weight="700" fill="#022c22">{$initial}</text>
</svg>
SVG;

        return 'data:image/svg+xml;base64,'.base64_encode($svg);
    }

    /**
     * Human-readable role label for the profile header badge.
     */
    public function profileRoleLabel(): string
    {
        $role = $this->getRoleNames()->first();

        if (is_string($role) && $role !== '') {
            return $role;
        }

        return $this->tenant_id === null
            ? 'مشرف عام - Super Admin'
            : 'مستخدم';
    }

    /**
     * Whether this user may open the Super Admin / Platform Console (`/admin/*`).
     *
     * Platform operators have `tenant_id = null` (Super Admin / Support Admin).
     * Named admin roles are also accepted for future Spatie-backed operator accounts.
     */
    public function canAccessPlatformConsole(): bool
    {
        if ($this->tenant_id === null) {
            return true;
        }

        return $this->hasAnyRole([
            'Super Admin',
            'Support Admin',
            'Admin',
            'super_admin',
            'support_admin',
            'admin',
        ]);
    }
}
