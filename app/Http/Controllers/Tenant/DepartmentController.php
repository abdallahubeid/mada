<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreDepartmentRequest;
use App\Http\Requests\Tenant\UpdateDepartmentRequest;
use App\Services\Tenancy\PlanLimitGuard;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Collection;

class DepartmentController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly PlanLimitGuard $planLimitGuard,
        private readonly TrashManager $trash,
    ) {}

    public function index(): View
    {
        $departments = Department::query()
            ->with(['head'])
            ->withCount('employees')
            ->orderBy('name')
            ->paginate(config('app.paginate_page'));

        return view('tenant.hr.departments.index', [
            'departments' => $departments,
        ]);
    }

    public function create(): View
    {
        return view('tenant.hr.departments.create', [
            'department' => new Department,
            'parents' => $this->parentOptions(),
            'heads' => $this->headOptions(),
        ]);
    }

    public function store(StoreDepartmentRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        abort_unless($tenant !== null, 403);

        $limit = $this->planLimitGuard->assertCanCreate($tenant, 'departments');
        if (! $limit['allowed']) {
            flash()->error("وصلت إلى الحد الأقصى للأقسام في خطتك ({$limit['used']}/{$limit['limit']}).");

            return redirect()->back()->withInput();
        }

        Department::query()->create($request->validated());
        $this->planLimitGuard->evaluateAfterCreate($tenant, 'departments');

        flash()->success('تم إنشاء القسم بنجاح.');

        return redirect()->route('hr.departments.index');
    }

    public function edit(Department $department): View
    {
        return view('tenant.hr.departments.edit', [
            'department' => $department,
            'parents' => $this->parentOptions($department->id),
            'heads' => $this->headOptions(),
        ]);
    }

    public function update(UpdateDepartmentRequest $request, Department $department): RedirectResponse
    {
        $department->update($request->validated());

        flash()->info('تم تحديث القسم بنجاح.');

        return redirect()->route('hr.departments.index');
    }

    public function destroy(Department $department): RedirectResponse
    {
        if ($department->children()->exists()) {
            flash()->error('لا يمكن حذف قسم يحتوي على أقسام فرعية. انقل الأقسام الفرعية أولاً.');

            return redirect()->route('hr.departments.index');
        }

        if ($department->employees()->exists()) {
            flash()->error('لا يمكن حذف قسم مرتبط بموظفين. انقل الموظفين أولاً.');

            return redirect()->route('hr.departments.index');
        }

        $department->delete();

        $this->trash->flashSoftDeleted('تم حذف القسم بنجاح.', 'departments', $department);

        return redirect()->route('hr.departments.index');
    }

    /**
     * @return Collection<int, string>
     */
    private function parentOptions(?int $excludeId = null)
    {
        return Department::query()
            ->when($excludeId !== null, fn ($query) => $query->whereKeyNot($excludeId))
            ->orderBy('name')
            ->pluck('name', 'id');
    }

    /**
     * @return Collection<int, string>
     */
    private function headOptions()
    {
        return Employee::query()
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get()
            ->mapWithKeys(fn (Employee $employee): array => [
                $employee->id => $employee->full_name,
            ]);
    }
}
