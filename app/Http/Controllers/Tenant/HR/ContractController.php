<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\ContractLifecycleChanged;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreEmployeeContractRequest;
use App\Http\Requests\Tenant\UpdateEmployeeContractRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ContractController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $expiringSoon = EmployeeContract::query()
            ->with('employee')
            ->where('status', ContractStatus::Active)
            ->whereNotNull('end_date')
            ->whereBetween('end_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()])
            ->orderBy('end_date')
            ->get();

        $contracts = EmployeeContract::query()
            ->with('employee')
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->when(
                $request->boolean('expiring'),
                fn ($query) => $query
                    ->where('status', ContractStatus::Active)
                    ->whereNotNull('end_date')
                    ->whereBetween('end_date', [now()->startOfDay(), now()->addDays(30)->endOfDay()]),
            )
            ->latest('start_date')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.hr.contracts.index', [
            'contracts' => $contracts,
            'expiringSoon' => $expiringSoon,
            'statuses' => ContractStatus::cases(),
            'filters' => [
                'status' => (string) $request->string('status', 'all'),
                'expiring' => $request->boolean('expiring'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('tenant.hr.contracts.create', [
            'contract' => new EmployeeContract([
                'status' => ContractStatus::Active,
                'contract_type' => ContractType::FullTime,
                'start_date' => now()->toDateString(),
            ]),
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreEmployeeContractRequest $request): RedirectResponse
    {
        $contract = EmployeeContract::query()->create($request->validated());

        event(new ContractLifecycleChanged($contract, 'created'));

        flash()->success('تم إنشاء العقد بنجاح.');

        return redirect()->route('hr.contracts.index');
    }

    public function edit(EmployeeContract $contract): View
    {
        $this->ensureTenantContract($contract);

        return view('tenant.hr.contracts.edit', [
            'contract' => $contract,
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateEmployeeContractRequest $request, EmployeeContract $contract): RedirectResponse
    {
        $this->ensureTenantContract($contract);

        $contract->update($request->validated());
        $contract->refresh();

        $action = in_array($contract->status, [
            ContractStatus::Terminated,
            ContractStatus::Expired,
        ], true) ? 'terminated' : 'updated';

        event(new ContractLifecycleChanged($contract, $action));

        flash()->info('تم تحديث العقد بنجاح.');

        return redirect()->route('hr.contracts.index');
    }

    public function destroy(EmployeeContract $contract): RedirectResponse
    {
        $this->ensureTenantContract($contract);

        $contract->delete();

        $this->trash->flashSoftDeleted('تم حذف العقد بنجاح.', 'contracts', $contract);

        return redirect()->route('hr.contracts.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get()
                ->mapWithKeys(fn (Employee $employee): array => [
                    $employee->id => $employee->full_name,
                ]),
            'types' => ContractType::cases(),
            'statuses' => ContractStatus::cases(),
        ];
    }

    private function ensureTenantContract(EmployeeContract $contract): void
    {
        abort_unless(
            (int) $contract->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
