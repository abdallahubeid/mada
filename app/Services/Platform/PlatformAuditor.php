<?php

namespace App\Services\Platform;

use App\Models\PlatformAuditLog;
use App\Services\Admin\AdminDashboard;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Request;

/**
 * Records Super Admin CMS mutations for NFR-05 auditability.
 */
class PlatformAuditor
{
    /**
     * @param  array<string, mixed>  $meta
     */
    public function log(string $action, ?Model $subject = null, array $meta = []): PlatformAuditLog
    {
        $log = PlatformAuditLog::query()->create([
            'user_id' => Auth::id(),
            'action' => $action,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'meta' => $meta === [] ? null : $meta,
            'ip_address' => Request::ip(),
        ]);

        AdminDashboard::flush();

        return $log;
    }
}
