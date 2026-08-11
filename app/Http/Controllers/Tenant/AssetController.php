<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Enums\AssetCategory;
use App\Domain\Tenancy\Enums\AssetCondition;
use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\AssetAssignment;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\AssetAssigned;
use App\Events\Tenancy\AssetReturned;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\AssignAssetRequest;
use App\Http\Requests\Tenant\ReturnAssetRequest;
use App\Http\Requests\Tenant\StoreAssetRequest;
use App\Http\Requests\Tenant\UpdateAssetRequest;
use App\Services\Tenancy\TenantAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AssetController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditor $auditor,
    ) {}

    public function index(Request $request): View
    {
        $assets = Asset::query()
            ->with(['currentAssignment.employee'])
            ->when(
                $request->filled('status') && (string) $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->when(
                $request->filled('category') && (string) $request->string('category') !== 'all',
                fn ($query) => $query->where('category', (string) $request->string('category')),
            )
            ->when($request->filled('q'), function ($query) use ($request): void {
                $q = '%'.mb_strtolower((string) $request->string('q')).'%';
                $query->where(function ($inner) use ($q): void {
                    $inner->whereRaw('LOWER(name) like ?', [$q])
                        ->orWhereRaw('LOWER(asset_code) like ?', [$q])
                        ->orWhereRaw("LOWER(COALESCE(serial_number, '')) like ?", [$q]);
                });
            })
            ->orderBy('asset_code')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        $kpis = [
            'total' => Asset::query()->count(),
            'assigned' => Asset::query()->where('status', AssetStatus::Assigned)->count(),
            'available' => Asset::query()->where('status', AssetStatus::Available)->count(),
            'maintenance' => Asset::query()->where('status', AssetStatus::UnderMaintenance)->count(),
        ];

        return view('tenant.assets.index', [
            'assets' => $assets,
            'kpis' => $kpis,
            'categories' => AssetCategory::cases(),
            'statuses' => AssetStatus::cases(),
            'conditions' => AssetCondition::cases(),
            'employees' => Employee::query()
                ->where('status', EmployeeStatus::Active)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get(['id', 'first_name', 'last_name']),
            'filters' => [
                'q' => (string) $request->string('q'),
                'status' => (string) $request->string('status', 'all'),
                'category' => (string) $request->string('category', 'all'),
            ],
            'canManage' => auth()->user()?->can('hr.assets.manage') ?? false,
            'nextCode' => Asset::nextAssetCode(),
        ]);
    }

    public function store(StoreAssetRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $asset = Asset::query()->create([
            ...$data,
            'asset_code' => filled($data['asset_code'] ?? null)
                ? strtoupper((string) $data['asset_code'])
                : Asset::nextAssetCode(),
            'status' => $data['status'] ?? AssetStatus::Available->value,
        ]);

        $this->auditor->log('asset.created', 'hr', $asset, [
            'asset_code' => $asset->asset_code,
            'name' => $asset->name,
        ]);

        flash()->success('تم إضافة الأصل بنجاح.');

        return redirect()->route('tenant.assets.index');
    }

    public function update(UpdateAssetRequest $request, Asset $asset): RedirectResponse
    {
        $data = $request->validated();

        if ($asset->status === AssetStatus::Assigned && ($data['status'] ?? null) !== AssetStatus::Assigned->value) {
            flash()->error('لا يمكن تغيير حالة أصل مُسند. أعد الأصل أولاً.');

            return redirect()->route('tenant.assets.index');
        }

        $asset->update([
            ...$data,
            'asset_code' => strtoupper((string) $data['asset_code']),
        ]);

        $this->auditor->log('asset.updated', 'hr', $asset, [
            'asset_code' => $asset->asset_code,
            'status' => $asset->status->value,
        ]);

        flash()->info('تم تحديث الأصل بنجاح.');

        return redirect()->route('tenant.assets.index');
    }

    public function assign(AssignAssetRequest $request, Asset $asset): RedirectResponse
    {
        if ($asset->status !== AssetStatus::Available) {
            flash()->error('يمكن إسناد الأصول المتاحة فقط.');

            return redirect()->route('tenant.assets.index');
        }

        $data = $request->validated();

        $assignment = DB::transaction(function () use ($asset, $data, $request): AssetAssignment {
            $assignment = AssetAssignment::query()->create([
                'asset_id' => $asset->id,
                'employee_id' => $data['employee_id'],
                'assigned_at' => $data['assigned_at'] ?? now(),
                'condition_on_assign' => $data['condition_on_assign'],
                'notes' => $data['notes'] ?? null,
                'assigned_by' => $request->user()?->id,
            ]);

            $asset->update(['status' => AssetStatus::Assigned]);

            return $assignment;
        });

        $this->auditor->log('asset.assigned', 'hr', $asset, [
            'employee_id' => (int) $data['employee_id'],
            'asset_code' => $asset->asset_code,
        ]);

        event(new AssetAssigned($asset->fresh(), $assignment->fresh()));

        flash()->success('تم إسناد الأصل للموظف بنجاح.');

        return redirect()->route('tenant.assets.index');
    }

    public function returnAsset(ReturnAssetRequest $request, Asset $asset): RedirectResponse
    {
        $assignment = $asset->currentAssignment;

        if ($asset->status !== AssetStatus::Assigned || $assignment === null) {
            flash()->error('لا يوجد إسناد نشط لهذا الأصل.');

            return redirect()->route('tenant.assets.index');
        }

        $data = $request->validated();
        $nextStatus = AssetStatus::from($data['status'] ?? AssetStatus::Available->value);

        if (! in_array($nextStatus, [AssetStatus::Available, AssetStatus::UnderMaintenance, AssetStatus::Retired], true)) {
            $nextStatus = AssetStatus::Available;
        }

        DB::transaction(function () use ($asset, $assignment, $data, $nextStatus): void {
            $assignment->update([
                'returned_at' => $data['returned_at'] ?? now(),
                'condition_on_return' => $data['condition_on_return'],
                'notes' => filled($data['notes'] ?? null)
                    ? trim(($assignment->notes ? $assignment->notes."\n" : '').$data['notes'])
                    : $assignment->notes,
            ]);

            $asset->update(['status' => $nextStatus]);
        });

        $this->auditor->log('asset.returned', 'hr', $asset, [
            'employee_id' => $assignment->employee_id,
            'status' => $nextStatus->value,
        ]);

        event(new AssetReturned(
            $asset->fresh(),
            $assignment->fresh(),
            $nextStatus,
            $request->user()?->id,
        ));

        flash()->success('تم تسجيل إعادة الأصل بنجاح.');

        return redirect()->route('tenant.assets.index');
    }

    public function employeeCustody(Employee $employee): View
    {
        abort_unless(
            (int) $employee->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );

        $active = AssetAssignment::query()
            ->with('asset')
            ->where('employee_id', $employee->id)
            ->whereNull('returned_at')
            ->latest('assigned_at')
            ->get();

        $history = AssetAssignment::query()
            ->with('asset')
            ->where('employee_id', $employee->id)
            ->whereNotNull('returned_at')
            ->latest('returned_at')
            ->paginate(config('app.paginate_page'));

        return view('tenant.assets.employee', [
            'employee' => $employee,
            'activeAssignments' => $active,
            'history' => $history,
            'canManage' => auth()->user()?->can('hr.assets.manage') ?? false,
        ]);
    }
}
