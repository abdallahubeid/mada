<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Actions\ApprovePayrollRun;
use App\Domain\Finance\Actions\MarkPayrollRunPaid;
use App\Domain\Finance\Actions\RecalculatePayrollRunTotals;
use App\Domain\Finance\Actions\RecordPayrollAdjustment;
use App\Domain\Finance\Actions\RejectPayrollRun;
use App\Domain\Finance\Actions\SubmitPayrollRunForApproval;
use App\Domain\Finance\Enums\PayrollRunStatus;
use App\Domain\Finance\Exceptions\LockedFinancialRecordException;
use App\Domain\Finance\Exceptions\PayrollAdjustmentException;
use App\Domain\Finance\Exceptions\PayrollRunException;
use App\Domain\Finance\Exceptions\PayrollRunTransitionException;
use App\Domain\Finance\Models\PayrollRun;
use App\Domain\Finance\Models\Payslip;
use App\Domain\Finance\Models\PayslipLineItem;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Finance\RejectPayrollRunRequest;
use App\Http\Requests\Tenant\Finance\StorePayrollAdjustmentRequest;
use App\Http\Requests\Tenant\Finance\StorePayrollRunRequest;
use App\Http\Requests\Tenant\Finance\UpdatePayrollRunRequest;
use App\Services\Finance\PayrollRunBuilder;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class PayrollRunController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
        private readonly PayrollRunBuilder $builder,
    ) {}

    public function index(Request $request): View
    {
        $runs = PayrollRun::query()
            ->with(['maker', 'approver'])
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('period', 'like', '%'.$request->string('search').'%'),
            )
            ->latest('period')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.finance.payroll-runs.index', [
            'runs' => $runs,
            'statuses' => PayrollRunStatus::cases(),
            'filters' => [
                'status' => (string) $request->string('status', 'all'),
                'search' => (string) $request->string('search'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('tenant.finance.payroll-runs.create', [
            'run' => new PayrollRun(['period' => now()->format('Y-m')]),
            'action' => route('finance.payroll-runs.store'),
            'method' => 'POST',
        ]);
    }

    public function store(StorePayrollRunRequest $request): RedirectResponse
    {
        try {
            $run = $this->builder->build(
                (string) $request->validated('period'),
                $request->user(),
            );
        } catch (PayrollRunException $exception) {
            // Every builder guard lands here — empty ledger, unresolved days,
            // unpriced contracts, mixed currencies. The message names the fix.
            flash()->error($exception->getMessage());

            return back()->withInput();
        }

        flash()->success('تم إنشاء مسودة مسيرة الرواتب بنجاح.');

        return redirect()->route('finance.payroll-runs.show', $run);
    }

    public function show(PayrollRun $payrollRun): View
    {
        $this->ensureTenantRun($payrollRun);

        return view('tenant.finance.payroll-runs.show', [
            'run' => $payrollRun->load(['maker', 'approver']),
            'payslips' => $payrollRun->payslips()
                ->with('employee')
                ->orderBy('employee_name')
                ->paginate(config('app.paginate_page')),
            'adjustments' => $payrollRun->adjustments()->latest('id')->get(),
            // Locked payslips this draft could carry a correction for (BR-603).
            'correctablePayslips' => $payrollRun->status->isEditable()
                ? Payslip::query()
                    ->with('payrollRun')
                    ->whereIn('employee_id', $payrollRun->payslips()->pluck('employee_id'))
                    ->whereHas('payrollRun', fn ($query) => $query->whereIn('status', [
                        PayrollRunStatus::Approved->value,
                        PayrollRunStatus::Paid->value,
                    ]))
                    ->get()
                : collect(),
        ]);
    }

    public function edit(PayrollRun $payrollRun): View
    {
        $this->ensureTenantRun($payrollRun);
        $this->ensureEditable($payrollRun);

        return view('tenant.finance.payroll-runs.edit', [
            'run' => $payrollRun,
            'action' => route('finance.payroll-runs.update', $payrollRun),
            'method' => 'PUT',
            'lineItems' => PayslipLineItem::query()
                ->whereIn('payslip_id', $payrollRun->payslips()->pluck('id'))
                ->with('payslip')
                ->orderBy('payslip_id')
                ->orderBy('sort_order')
                ->get(),
        ]);
    }

    public function update(UpdatePayrollRunRequest $request, PayrollRun $payrollRun): RedirectResponse
    {
        $this->ensureTenantRun($payrollRun);
        $this->ensureEditable($payrollRun);

        $amounts = $request->validated('line_items') ?? [];

        try {
            DB::transaction(function () use ($request, $payrollRun, $amounts): void {
                $payrollRun->update(['notes' => $request->validated('notes')]);

                if ($amounts !== []) {
                    $this->applyLineItemAmounts($payrollRun, $amounts);
                }

                (new RecalculatePayrollRunTotals)->handle($payrollRun);
            });
        } catch (LockedFinancialRecordException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->info('تم تحديث مسيرة الرواتب بنجاح.');

        return redirect()->route('finance.payroll-runs.show', $payrollRun);
    }

    public function destroy(PayrollRun $payrollRun): RedirectResponse
    {
        $this->ensureTenantRun($payrollRun);

        try {
            $payrollRun->delete();
        } catch (LockedFinancialRecordException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        // Soft-deleted runs surface in the shared /app/trash console via
        // TrashableResourceCatalog — no module-specific trash view.
        $this->trash->flashSoftDeleted('تم حذف مسيرة الرواتب بنجاح.', 'payroll-runs', $payrollRun);

        return redirect()->route('finance.payroll-runs.index');
    }

    /**
     * Record a correction to a LOCKED run, carried by this draft (BR-603).
     *
     * The only legal way to fix an approved payslip — the run itself stays
     * immutable and the money moves in this cycle instead.
     */
    public function adjust(
        StorePayrollAdjustmentRequest $request,
        PayrollRun $payrollRun,
        RecordPayrollAdjustment $action,
    ): RedirectResponse {
        $this->ensureTenantRun($payrollRun);

        $payslip = Payslip::query()->findOrFail($request->validated('original_payslip_id'));

        try {
            $action->handle(
                $payrollRun,
                $payslip,
                (int) round(((float) $request->validated('amount')) * 100),
                (string) $request->validated('reason'),
                $request->user(),
            );
        } catch (PayrollAdjustmentException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->success('تم تسجيل قيد التسوية على هذه المسيرة.');

        return redirect()->route('finance.payroll-runs.show', $payrollRun);
    }

    public function recalculate(PayrollRun $payrollRun): RedirectResponse
    {
        $this->ensureTenantRun($payrollRun);
        $this->ensureEditable($payrollRun);

        (new RecalculatePayrollRunTotals)->handle($payrollRun);

        flash()->info('تمت إعادة احتساب إجماليات المسيرة.');

        return redirect()->route('finance.payroll-runs.show', $payrollRun);
    }

    public function submit(PayrollRun $payrollRun, SubmitPayrollRunForApproval $action): RedirectResponse
    {
        return $this->runTransition(
            $payrollRun,
            fn () => $action->handle($payrollRun),
            'تم رفع المسيرة للاعتماد.',
        );
    }

    public function approve(PayrollRun $payrollRun, ApprovePayrollRun $action): RedirectResponse
    {
        return $this->runTransition(
            $payrollRun,
            fn () => $action->handle($payrollRun, request()->user()),
            'تم اعتماد المسيرة وقفلها نهائياً.',
        );
    }

    public function reject(RejectPayrollRunRequest $request, PayrollRun $payrollRun, RejectPayrollRun $action): RedirectResponse
    {
        // Warning, not success: the run did not advance. Green here would tell
        // the approver the opposite of what happened.
        return $this->runTransition(
            $payrollRun,
            fn () => $action->handle($payrollRun, $request->user(), (string) $request->validated('rejection_reason')),
            'تم رفض المسيرة وإعادتها إلى المسودات لمعالجة الملاحظات.',
            'warning',
        );
    }

    public function disburse(PayrollRun $payrollRun, MarkPayrollRunPaid $action): RedirectResponse
    {
        return $this->runTransition(
            $payrollRun,
            fn () => $action->handle($payrollRun),
            'تم تسجيل صرف المسيرة.',
        );
    }

    /**
     * Every state transition shares one shape: guard the tenant, run the
     * action, translate a domain refusal into an error toast rather than a 500.
     *
     * `$tone` selects the toast styling so the colour matches the outcome —
     * an advance is success, a rejection is a warning.
     *
     * @param  'success'|'info'|'warning'  $tone
     */
    private function runTransition(
        PayrollRun $payrollRun,
        callable $transition,
        string $message,
        string $tone = 'success',
    ): RedirectResponse {
        $this->ensureTenantRun($payrollRun);

        try {
            $transition();
        } catch (PayrollRunTransitionException|LockedFinancialRecordException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->{$tone}($message);

        return redirect()->route('finance.payroll-runs.show', $payrollRun);
    }

    /**
     * @param  array<int|string, mixed>  $amounts  keyed by line item id, MAJOR units
     */
    private function applyLineItemAmounts(PayrollRun $payrollRun, array $amounts): void
    {
        $lineItems = PayslipLineItem::query()
            ->whereIn('payslip_id', $payrollRun->payslips()->pluck('id'))
            ->whereIn('id', array_keys($amounts))
            ->get();

        foreach ($lineItems as $lineItem) {
            $major = $amounts[$lineItem->id] ?? null;

            if ($major === null || $major === '') {
                continue;
            }

            // Major -> minor units, sign taken from the line's own kind so the
            // form can never flip an allowance into a deduction (ADR-20).
            $minor = (int) round(((float) $major) * 100);
            $signed = $lineItem->kind->permits($minor) ? $minor : -$minor;

            $lineItem->update(['amount' => $signed]);
        }

        $this->refreshPayslipTotals($payrollRun);
    }

    /**
     * Re-roll each payslip's allowance/deduction columns and net from its own
     * line items. The base and absence figures are untouched — they come from
     * the Work Ledger and are not editable here.
     */
    private function refreshPayslipTotals(PayrollRun $payrollRun): void
    {
        $payrollRun->payslips()->with('lineItems')->get()->each(function ($payslip): void {
            $allowances = (int) $payslip->lineItems->where('amount', '>=', 0)->sum('amount');
            $deductions = (int) $payslip->lineItems->where('amount', '<', 0)->sum('amount');
            $gross = $payslip->base_amount + $allowances;

            $payslip->update([
                'allowances_total' => $allowances,
                'deductions_total' => $deductions,
                'gross_amount' => $gross,
                'net_amount' => $gross + $payslip->absence_deduction + $deductions,
            ]);
        });
    }

    private function ensureEditable(PayrollRun $payrollRun): void
    {
        abort_if($payrollRun->isLocked(), 403, 'مسيرة الرواتب المعتمدة غير قابلة للتعديل.');
    }

    private function ensureTenantRun(PayrollRun $payrollRun): void
    {
        abort_unless(
            (int) $payrollRun->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
