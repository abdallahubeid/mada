<?php

namespace App\Services\Tenancy;

use App\Domain\Tenancy\Models\AuditLog;
use App\Domain\Tenancy\TenantContext;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Records tenant-scoped mutations for Owner audit visibility.
 */
class TenantAuditor
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @param  array<string, mixed>|null  $changes
     */
    public function log(
        string $action,
        string $module = 'system',
        ?Model $subject = null,
        ?array $changes = null,
    ): ?AuditLog {
        $tenantId = $this->tenantContext->getTenantId();

        if ($tenantId === null) {
            return null;
        }

        return AuditLog::query()->create([
            'tenant_id' => $tenantId,
            'user_id' => Auth::id(),
            'action' => $action,
            'module' => $module,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'changes' => $changes,
            'ip_address' => Request::ip(),
        ]);
    }
}
