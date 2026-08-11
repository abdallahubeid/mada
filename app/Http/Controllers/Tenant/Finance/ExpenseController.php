<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Actions\ApproveExpenseAction;
use App\Domain\Finance\Actions\DisburseExpenseAction;
use App\Domain\Finance\Actions\RejectExpenseAction;
use App\Domain\Finance\Actions\SubmitExpenseAction;
use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Exceptions\ExpenseTransitionException;
use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\ExpenseCategory;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Finance\RejectExpenseRequest;
use App\Http\Requests\Tenant\Finance\StoreExpenseRequest;
use App\Http\Requests\Tenant\Finance\UpdateExpenseRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class ExpenseController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(Request $request): View
    {
        $expenses = Expense::query()
            ->with(['category', 'employee'])
            ->when(
                $request->filled('status') && $request->string('status') !== 'all',
                fn ($query) => $query->where('status', (string) $request->string('status')),
            )
            ->when(
                $request->filled('category'),
                fn ($query) => $query->where('expense_category_id', $request->integer('category')),
            )
            ->when(
                $request->filled('search'),
                fn ($query) => $query->where('title', 'like', '%'.$request->string('search').'%'),
            )
            ->latest('expense_date')
            ->paginate(config('app.paginate_page'))
            ->withQueryString();

        return view('tenant.finance.expenses.index', [
            'expenses' => $expenses,
            'statuses' => ExpenseStatus::cases(),
            'categories' => ExpenseCategory::query()->orderBy('sort_order')->get(),
            'filters' => [
                'status' => (string) $request->string('status', 'all'),
                'category' => (string) $request->string('category'),
                'search' => (string) $request->string('search'),
            ],
        ]);
    }

    public function create(): View
    {
        return view('tenant.finance.expenses.create', [
            'expense' => new Expense([
                'expense_date' => now()->toDateString(),
                'currency' => OrgSetting::query()->value('currency') ?? 'SAR',
            ]),
            'action' => route('finance.expenses.store'),
            'method' => 'POST',
            ...$this->formOptions(),
        ]);
    }

    public function store(StoreExpenseRequest $request): RedirectResponse
    {
        $expense = Expense::query()->create($this->payload($request->validated(), $request));

        flash()->success('تم تسجيل المصروف بنجاح.');

        return redirect()->route('finance.expenses.show', $expense);
    }

    public function show(Expense $expense): View
    {
        $this->ensureTenantExpense($expense);

        return view('tenant.finance.expenses.show', [
            'expense' => $expense->load(['category', 'employee', 'submitter', 'decider', 'approvals.decidedBy']),
        ]);
    }

    public function edit(Expense $expense): View
    {
        $this->ensureTenantExpense($expense);
        abort_if($expense->isLocked(), 403, 'المصروف المعتمد غير قابل للتعديل.');

        return view('tenant.finance.expenses.edit', [
            'expense' => $expense,
            'action' => route('finance.expenses.update', $expense),
            'method' => 'PUT',
            ...$this->formOptions(),
        ]);
    }

    public function update(UpdateExpenseRequest $request, Expense $expense): RedirectResponse
    {
        $this->ensureTenantExpense($expense);

        try {
            $expense->update($this->payload($request->validated(), $request, $expense));
        } catch (ExpenseTransitionException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->info('تم تحديث المصروف بنجاح.');

        return redirect()->route('finance.expenses.show', $expense);
    }

    public function destroy(Expense $expense): RedirectResponse
    {
        $this->ensureTenantExpense($expense);

        try {
            $expense->delete();
        } catch (ExpenseTransitionException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        $this->trash->flashSoftDeleted('تم حذف المصروف بنجاح.', 'expenses', $expense);

        return redirect()->route('finance.expenses.index');
    }

    public function submit(Expense $expense, SubmitExpenseAction $action): RedirectResponse
    {
        return $this->transition($expense, fn () => $action->handle($expense, request()->user()), 'تم رفع المصروف للاعتماد.');
    }

    public function approve(Expense $expense, ApproveExpenseAction $action): RedirectResponse
    {
        return $this->transition($expense, fn () => $action->handle($expense, request()->user()), 'تم اعتماد المصروف.');
    }

    public function reject(RejectExpenseRequest $request, Expense $expense, RejectExpenseAction $action): RedirectResponse
    {
        // Warning, not success — the claim was turned down, and the claimant
        // can correct and resubmit this same record.
        return $this->transition(
            $expense,
            fn () => $action->handle($expense, $request->user(), (string) $request->validated('rejection_reason')),
            'تم رفض المصروف وإعادته لمقدّمه للتعديل.',
            'warning',
        );
    }

    public function disburse(Expense $expense, DisburseExpenseAction $action): RedirectResponse
    {
        return $this->transition($expense, fn () => $action->handle($expense), 'تم تسجيل صرف المصروف.');
    }

    /**
     * @param  'success'|'info'|'warning'  $tone
     */
    private function transition(
        Expense $expense,
        callable $callback,
        string $message,
        string $tone = 'success',
    ): RedirectResponse {
        $this->ensureTenantExpense($expense);

        try {
            $callback();
        } catch (ExpenseTransitionException $exception) {
            flash()->error($exception->getMessage());

            return back();
        }

        flash()->{$tone}($message);

        return redirect()->route('finance.expenses.show', $expense);
    }

    /**
     * Convert the submitted MAJOR-unit amount into minor units and attach the
     * receipt if one was uploaded (ADR-20).
     *
     * @param  array<string, mixed>  $validated
     * @return array<string, mixed>
     */
    private function payload(array $validated, Request $request, ?Expense $existing = null): array
    {
        $validated['amount'] = (int) round(((float) $validated['amount']) * 100);
        $validated['currency'] = $existing?->currency
            ?? OrgSetting::query()->value('currency')
            ?? 'SAR';

        if ($request->hasFile('receipt')) {
            $validated['receipt_path'] = $request->file('receipt')->store('expenses', 'custom');
        }

        if ($existing === null) {
            $validated['submitted_by'] = $request->user()?->id;
        }

        return $validated;
    }

    /**
     * @return array<string, mixed>
     */
    private function formOptions(): array
    {
        return [
            'categories' => ExpenseCategory::query()->active()->orderBy('sort_order')->get(),
            'employees' => Employee::query()
                ->orderBy('first_name')
                ->get()
                ->mapWithKeys(fn (Employee $employee): array => [$employee->id => $employee->full_name]),
        ];
    }

    private function ensureTenantExpense(Expense $expense): void
    {
        abort_unless(
            (int) $expense->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
