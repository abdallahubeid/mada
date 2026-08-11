<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Models\ExpenseCategory;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Finance\StoreExpenseCategoryRequest;
use App\Http\Requests\Tenant\Finance\UpdateExpenseCategoryRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class ExpenseCategoryController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TrashManager $trash,
    ) {}

    public function index(): View
    {
        return view('tenant.finance.expense-categories.index', [
            'categories' => ExpenseCategory::query()
                ->withCount('expenses')
                ->orderBy('sort_order')
                ->orderBy('id')
                ->paginate(config('app.paginate_page')),
        ]);
    }

    public function create(): View
    {
        return view('tenant.finance.expense-categories.create', [
            'category' => new ExpenseCategory,
            'action' => route('finance.expense-categories.store'),
            'method' => 'POST',
        ]);
    }

    public function store(StoreExpenseCategoryRequest $request): RedirectResponse
    {
        ExpenseCategory::query()->create($request->validated());

        flash()->success('تم إضافة التصنيف بنجاح.');

        return redirect()->route('finance.expense-categories.index');
    }

    public function edit(ExpenseCategory $expenseCategory): View
    {
        $this->ensureTenantCategory($expenseCategory);

        return view('tenant.finance.expense-categories.edit', [
            'category' => $expenseCategory,
            'action' => route('finance.expense-categories.update', $expenseCategory),
            'method' => 'PUT',
        ]);
    }

    public function update(UpdateExpenseCategoryRequest $request, ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureTenantCategory($expenseCategory);

        $expenseCategory->update($request->validated());

        flash()->info('تم تحديث التصنيف بنجاح.');

        return redirect()->route('finance.expense-categories.index');
    }

    public function destroy(ExpenseCategory $expenseCategory): RedirectResponse
    {
        $this->ensureTenantCategory($expenseCategory);

        // Expenses keep their category_id; the FK is nullOnDelete only on hard
        // delete, and this is a soft delete, so historical records stay intact
        // and simply reference a trashed category.
        $expenseCategory->delete();

        $this->trash->flashSoftDeleted('تم حذف التصنيف بنجاح.', 'expense-categories', $expenseCategory);

        return redirect()->route('finance.expense-categories.index');
    }

    private function ensureTenantCategory(ExpenseCategory $category): void
    {
        abort_unless(
            (int) $category->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
