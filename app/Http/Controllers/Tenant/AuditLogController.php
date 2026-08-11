<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\AuditLog;
use App\Http\Controllers\Controller;
use App\Services\Tenancy\AuditLogPresenter;
use Illuminate\Contracts\View\View;
use Illuminate\Http\Request;

class AuditLogController extends Controller
{
    public function __construct(private readonly AuditLogPresenter $presenter) {}

    public function index(Request $request): View
    {
        $logs = AuditLog::query()
            ->with('user')
            ->when(
                $request->filled('module') && (string) $request->string('module') !== 'all',
                fn ($query) => $query->where('module', (string) $request->string('module')),
            )
            ->when(
                $request->filled('q'),
                function ($query) use ($request): void {
                    $term = (string) $request->string('q');
                    $query->where(function ($inner) use ($term): void {
                        $inner->where('action', 'like', '%'.$term.'%')
                            ->orWhere('module', 'like', '%'.$term.'%');
                    });
                },
            )
            ->latest('id')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        $modules = AuditLog::query()
            ->select('module')
            ->distinct()
            ->orderBy('module')
            ->pluck('module')
            ->mapWithKeys(fn (string $module) => [$module => $this->presenter->moduleLabel($module)]);

        $presented = $logs->getCollection()->mapWithKeys(
            fn (AuditLog $log) => [$log->id => $this->presenter->present($log)],
        );

        return view('tenant.audit-logs.index', [
            'logs' => $logs,
            'modules' => $modules,
            'presented' => $presented,
            'filters' => [
                'module' => (string) $request->string('module', 'all'),
                'q' => (string) $request->string('q'),
            ],
        ]);
    }
}
