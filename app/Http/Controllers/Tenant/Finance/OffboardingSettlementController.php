<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Actions\ApproveSettlementAction;
use App\Domain\Finance\Actions\DisburseSettlementAction;
use App\Domain\Finance\Actions\GenerateSettlementAction;
use App\Domain\Finance\Actions\SubmitSettlementAction;
use App\Domain\Finance\Enums\OffboardingReason;
use App\Domain\Finance\Enums\SettlementStatus;
use App\Domain\Finance\Exceptions\SettlementException;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Finance\StoreOffboardingSettlementRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class OffboardingSettlementController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $settlements = OffboardingSettlement::query()
            ->with(['employee', 'author', 'approver'])
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->latest('last_working_day')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.finance.offboarding.index', [
            'settlements' => $settlements,
            'statuses' => SettlementStatus::cases(),
            'filters' => ['status' => (string) $request->string('status', 'all')],
        ]);
    }

    public function create(): View
    {
        return view('tenant.finance.offboarding.create', [
            'employees' => Employee::query()
                ->where('status', EmployeeStatus::Active->value)
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn (Employee $employee): array => [$employee->id => $employee->full_name]),
            'reasons' => OffboardingReason::cases(),
        ]);
    }

    public function store(StoreOffboardingSettlementRequest $request, GenerateSettlementAction $action): RedirectResponse
    {
        $employee = Employee::query()->findOrFail($request->validated('employee_id'));

        try {
            $settlement = $action->handle(
                employee: $employee,
                lastWorkingDay: Carbon::parse((string) $request->validated('last_working_day')),
                reason: OffboardingReason::from((string) $request->validated('reason')),
                loanDeduction: (int) round(((float) ($request->validated('loan_deduction') ?? 0)) * 100),
                otherDeduction: (int) round(((float) ($request->validated('other_deduction') ?? 0)) * 100),
                author: $request->user(),
                notes: $request->validated('notes'),
            );
        } catch (SettlementException $exception) {
            flash()->error($exception->getMessage());

            return back()->withInput();
        }

        flash()->success('تم إنشاء مسودة تسوية نهاية الخدمة.');

        return redirect()->route('finance.offboarding.show', $settlement);
    }

    public function show(OffboardingSettlement $offboarding): View
    {
        $this->ensureTenantSettlement($offboarding);

        return view('tenant.finance.offboarding.show', [
            'settlement' => $offboarding->load(['employee', 'author', 'approver']),
        ]);
    }

    /**
     * Fixed light theme regardless of app theme (DESIGN_SYSTEM.md §2.2).
     */
    public function print(OffboardingSettlement $offboarding): View
    {
        $this->ensureTenantSettlement($offboarding);

        return view('tenant.finance.offboarding.print', [
            'settlement' => $offboarding->load('employee'),
        ]);
    }

    public function submit(OffboardingSettlement $offboarding, SubmitSettlementAction $action): RedirectResponse
    {
        return $this->transition($offboarding, fn () => $action->handle($offboarding), 'تم رفع التسوية للاعتماد.');
    }

    public function approve(OffboardingSettlement $offboarding, ApproveSettlementAction $action): RedirectResponse
    {
        return $this->transition(
            $offboarding,
            fn () => $action->handle($offboarding, request()->user()),
            'تم اعتماد التسوية وقفلها.',
        );
    }

    public function disburse(OffboardingSettlement $offboarding, DisburseSettlementAction $action): RedirectResponse
    {
        return $this->transition(
            $offboarding,
            fn () => $action->handle($offboarding),
            'تم صرف التسوية وإنهاء خدمة الموظف.',
        );
    }

    public function destroy(OffboardingSettlement $offboarding): RedirectResponse
    {
        $this->ensureTenantSettlement($offboarding);

        try {
            $offboarding->delete();
        } catch (SettlementException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        $this->trash->flashSoftDeleted('تم حذف التسوية بنجاح.', 'offboarding-settlements', $offboarding);

        return redirect()->route('finance.offboarding.index');
    }

    private function transition(OffboardingSettlement $settlement, callable $callback, string $message): RedirectResponse
    {
        $this->ensureTenantSettlement($settlement);

        try {
            $callback();
        } catch (SettlementException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->success($message);

        return redirect()->route('finance.offboarding.show', $settlement);
    }

    private function ensureTenantSettlement(OffboardingSettlement $settlement): void
    {
        abort_unless(
            (int) $settlement->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
