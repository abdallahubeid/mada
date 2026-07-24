<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

/**
 * Platform Audit Log (docs/MODULES.md §6, NFR-05). Platform-wide, immutable
 * record of permission/role changes, tenant transitions, settings changes and
 * Super Admin impersonation. Frontend slice: entries are mocked in-controller
 * and filtered by the `action`/`tenant`/`actor` query params.
 */
class AuditLogController extends Controller
{
    /**
     * @return list<array{id:int, time_abs:string, time_rel:string, actor:string, actor_type:string, action:string, target:string, ip:string, changes:list<array{field:string, from:string, to:string}>}>
     */
    private function entries(): array
    {
        return [
            ['id' => 1, 'time_abs' => '2026-07-21 11:42', 'time_rel' => 'قبل 12 دقيقة', 'actor' => 'مشرف المنصّة', 'actor_type' => 'admin', 'action' => 'approval', 'target' => 'شركة الابتكار', 'ip' => '156.203.44.10', 'changes' => [['field' => 'status', 'from' => 'pending_approval', 'to' => 'active']]],
            ['id' => 2, 'time_abs' => '2026-07-21 09:15', 'time_rel' => 'قبل ساعتين', 'actor' => 'النظام', 'actor_type' => 'system', 'action' => 'security_flag', 'target' => 'حساب مشرف', 'ip' => '45.86.200.7', 'changes' => [['field' => 'failed_attempts', 'from' => '3', 'to' => '5']]],
            ['id' => 3, 'time_abs' => '2026-07-21 08:03', 'time_rel' => 'قبل 4 ساعات', 'actor' => 'مشرف المنصّة', 'actor_type' => 'admin', 'action' => 'suspension', 'target' => 'متجر السلام', 'ip' => '156.203.44.10', 'changes' => [['field' => 'status', 'from' => 'active', 'to' => 'suspended'], ['field' => 'reason', 'from' => '—', 'to' => 'تأخر السداد']]],
            ['id' => 4, 'time_abs' => '2026-07-20 22:47', 'time_rel' => 'أمس', 'actor' => 'مشرف المنصّة', 'actor_type' => 'admin', 'action' => 'settings_change', 'target' => 'إعدادات المنصّة', 'ip' => '156.203.44.10', 'changes' => [['field' => 'auto_approve_tenants', 'from' => 'false', 'to' => 'true']]],
            ['id' => 5, 'time_abs' => '2026-07-20 18:20', 'time_rel' => 'أمس', 'actor' => 'مشرف المنصّة', 'actor_type' => 'admin', 'action' => 'impersonation', 'target' => 'مجموعة رواد', 'ip' => '156.203.44.10', 'changes' => [['field' => 'session', 'from' => 'none', 'to' => 'started']]],
            ['id' => 6, 'time_abs' => '2026-07-20 14:05', 'time_rel' => 'أمس', 'actor' => 'مشرف المنصّة', 'actor_type' => 'admin', 'action' => 'role_change', 'target' => 'مؤسسة التميّز', 'ip' => '156.203.44.10', 'changes' => [['field' => 'user.role', 'from' => 'Employee', 'to' => 'HR Manager']]],
            ['id' => 7, 'time_abs' => '2026-07-19 10:30', 'time_rel' => 'قبل يومين', 'actor' => 'النظام', 'actor_type' => 'system', 'action' => 'security_flag', 'target' => 'بوابة التسجيل', 'ip' => '102.44.19.88', 'changes' => [['field' => 'rate_limit', 'from' => 'ok', 'to' => 'exceeded']]],
            ['id' => 8, 'time_abs' => '2026-07-18 16:12', 'time_rel' => 'قبل 3 أيام', 'actor' => 'مشرف المنصّة', 'actor_type' => 'admin', 'action' => 'approval', 'target' => 'مؤسسة التميّز', 'ip' => '156.203.44.10', 'changes' => [['field' => 'status', 'from' => 'pending_approval', 'to' => 'active']]],
        ];
    }

    public function __invoke(Request $request): View
    {
        $entries = $this->entries();

        $actionTypes = [
            'approval' => 'موافقة',
            'suspension' => 'إيقاف',
            'role_change' => 'تغيير صلاحية',
            'impersonation' => 'انتحال شخصية',
            'settings_change' => 'تغيير إعدادات',
            'security_flag' => 'تنبيه أمني',
        ];

        $tenants = collect($this->entries())->pluck('target')->unique()->values()->all();

        // Optional filtering (frontend slice).
        $action = $request->query('action');
        $tenant = $request->query('tenant');
        $actor = $request->query('actor');

        $filtered = collect($entries)
            ->when($action, fn ($c) => $c->where('action', $action))
            ->when($tenant, fn ($c) => $c->where('target', $tenant))
            ->when($actor, fn ($c) => $c->filter(fn ($e): bool => str_contains($e['actor'], (string) $actor)))
            ->values()
            ->all();

        return view('admin.audit-log.index', [
            'entries' => $filtered,
            'actionTypes' => $actionTypes,
            'tenants' => $tenants,
            'filters' => ['action' => $action, 'tenant' => $tenant, 'actor' => $actor],
        ]);
    }
}
