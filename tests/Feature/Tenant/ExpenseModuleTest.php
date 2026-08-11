<?php

use App\Domain\Finance\Actions\ApproveExpenseAction;
use App\Domain\Finance\Actions\DisburseExpenseAction;
use App\Domain\Finance\Actions\RejectExpenseAction;
use App\Domain\Finance\Actions\SubmitExpenseAction;
use App\Domain\Finance\Enums\ExpenseStatus;
use App\Domain\Finance\Exceptions\ExpenseTransitionException;
use App\Domain\Finance\Models\Expense;
use App\Domain\Finance\Models\ExpenseCategory;
use App\Domain\Tenancy\ApprovableCatalog;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Domain\Tenancy\Models\Approval;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

function makeExpense(int $tenantId, array $attributes = []): Expense
{
    return Expense::query()->create(array_merge([
        'tenant_id' => $tenantId,
        'title' => 'تذاكر سفر',
        'expense_date' => '2026-08-10',
        'amount' => 125_000,
        'currency' => 'SAR',
        'is_claimable' => true,
        'status' => ExpenseStatus::Draft,
    ], $attributes));
}

// ---------------------------------------------------------------------------
// Approval workflow
// ---------------------------------------------------------------------------

test('an expense moves draft to pending to approved to paid', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $expense = makeExpense($owner->tenant_id);

    $expense = app(SubmitExpenseAction::class)->handle($expense, $owner);
    expect($expense->status)->toBe(ExpenseStatus::PendingApproval);

    $expense = app(ApproveExpenseAction::class)->handle($expense, $checker);
    expect($expense->status)->toBe(ExpenseStatus::Approved)
        ->and($expense->decided_by)->toBe($checker->id);

    $expense = app(DisburseExpenseAction::class)->handle($expense);
    expect($expense->status)->toBe(ExpenseStatus::Paid)
        ->and($expense->paid_at)->not->toBeNull();
});

test('submitting opens an approval carrying the expense morph alias', function () {
    // BR-902: approvals reference subjects by short alias, never FQCN.
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $expense = app(SubmitExpenseAction::class)->handle(makeExpense($owner->tenant_id), $owner);

    $storedType = DB::table('approvals')->where('approvable_id', $expense->id)->value('approvable_type');

    expect($storedType)->toBe(ApprovableCatalog::EXPENSE)
        ->and($storedType)->not->toContain('\\')
        ->and($expense->currentApproval)->not->toBeNull()
        ->and($expense->currentApproval->status)->toBe(ApprovalStatus::Pending);
});

test('the submitter cannot approve or reject their own claim', function () {
    // The primary abuse an expense workflow exists to prevent.
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $expense = app(SubmitExpenseAction::class)->handle(makeExpense($owner->tenant_id), $owner);

    expect(fn () => app(ApproveExpenseAction::class)->handle($expense, $owner))
        ->toThrow(ExpenseTransitionException::class, 'may not decide it');

    expect(fn () => app(RejectExpenseAction::class)->handle($expense, $owner, 'سبب'))
        ->toThrow(ExpenseTransitionException::class, 'may not decide it');

    expect($expense->refresh()->status)->toBe(ExpenseStatus::PendingApproval);
});

test('a rejected expense becomes editable again and can be resubmitted', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $expense = app(SubmitExpenseAction::class)->handle(makeExpense($owner->tenant_id), $owner);
    $expense = app(RejectExpenseAction::class)->handle($expense, $checker, 'الإيصال غير واضح');

    expect($expense->status)->toBe(ExpenseStatus::Rejected)
        ->and($expense->rejection_reason)->toBe('الإيصال غير واضح')
        ->and($expense->status->isEditable())->toBeTrue();

    // Resubmission attaches to the same subject, preserving its history.
    $expense = app(SubmitExpenseAction::class)->handle($expense, $owner);

    expect($expense->status)->toBe(ExpenseStatus::PendingApproval)
        ->and($expense->rejection_reason)->toBeNull()
        ->and(Approval::query()->where('approvable_id', $expense->id)->count())->toBe(2);
});

test('rejection requires a reason', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $expense = app(SubmitExpenseAction::class)->handle(makeExpense($owner->tenant_id), $owner);

    expect(fn () => app(RejectExpenseAction::class)->handle($expense, $checker, '  '))
        ->toThrow(ExpenseTransitionException::class, 'requires a reason');
});

test('a non claimable expense cannot be disbursed', function () {
    // It was settled directly and is owed to nobody.
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $expense = makeExpense($owner->tenant_id, ['is_claimable' => false]);
    $expense = app(ApproveExpenseAction::class)->handle(
        app(SubmitExpenseAction::class)->handle($expense, $owner), $checker
    );

    expect($expense->isDisbursable())->toBeFalse();

    expect(fn () => app(DisburseExpenseAction::class)->handle($expense))
        ->toThrow(ExpenseTransitionException::class, 'not claimable');
});

test('an expense cannot be submitted twice while a decision is open', function () {
    // BR-904: one non-terminal approval per subject.
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $expense = app(SubmitExpenseAction::class)->handle(makeExpense($owner->tenant_id), $owner);

    expect(fn () => app(SubmitExpenseAction::class)->handle($expense, $owner))
        ->toThrow(ExpenseTransitionException::class);

    expect(Approval::query()->where('approvable_id', $expense->id)->count())->toBe(1);
});

test('an approved expense is immutable and undeletable', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $expense = app(ApproveExpenseAction::class)->handle(
        app(SubmitExpenseAction::class)->handle(makeExpense($owner->tenant_id), $owner), $checker
    );

    expect(fn () => $expense->update(['amount' => 1]))
        ->toThrow(ExpenseTransitionException::class, 'can no longer be edited');

    expect(fn () => $expense->delete())
        ->toThrow(ExpenseTransitionException::class);

    expect(Expense::query()->count())->toBe(1);
});

test('a draft expense remains editable and deletable', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $expense = makeExpense($owner->tenant_id);

    $expense->update(['amount' => 90_000]);
    expect($expense->refresh()->amount)->toBe(90_000);

    $expense->delete();
    expect(Expense::query()->count())->toBe(0)
        ->and(Expense::withTrashed()->count())->toBe(1);
});

// ---------------------------------------------------------------------------
// HTTP
// ---------------------------------------------------------------------------

test('the expense index renders with an empty state', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('finance.expenses.index'))
        ->assertOk()
        ->assertSee('لا توجد مصروفات بعد.');
});

test('an expense is created through the form in minor units', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $category = ExpenseCategory::query()->create(['tenant_id' => $owner->tenant_id, 'name' => 'سفر']);

    $this->post(route('finance.expenses.store'), [
        'title' => 'فندق',
        'amount' => '1450.75',
        'expense_date' => '2026-08-11',
        'expense_category_id' => $category->id,
        'is_claimable' => 1,
    ])->assertRedirect();

    $expense = Expense::query()->firstOrFail();

    expect($expense->amount)->toBe(145_075)
        ->and($expense->currency)->toBe('SAR')
        ->and($expense->submitted_by)->toBe($owner->id)
        ->and($expense->status)->toBe(ExpenseStatus::Draft);

    expect(session('flasher')['message'] ?? null)->toBe('تم تسجيل المصروف بنجاح.');
});

test('a zero amount is rejected by validation', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->post(route('finance.expenses.store'), [
        'title' => 'صفر', 'amount' => '0', 'expense_date' => '2026-08-11',
    ])->assertSessionHasErrors('amount');
});

test('the full workflow drives through http', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $expense = makeExpense($owner->tenant_id, ['submitted_by' => $owner->id]);

    $this->post(route('finance.expenses.submit', $expense))->assertRedirect();
    expect($expense->refresh()->status)->toBe(ExpenseStatus::PendingApproval);

    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $checker->assignRole(TenantPermissionCatalog::ROLE_OWNER);
    $this->actingAs($checker);

    $this->post(route('finance.expenses.approve', $expense))->assertRedirect();
    expect($expense->refresh()->status)->toBe(ExpenseStatus::Approved);

    $this->post(route('finance.expenses.disburse', $expense))->assertRedirect();
    expect($expense->refresh()->status)->toBe(ExpenseStatus::Paid);
});

test('a domain refusal over http becomes an error toast, not a 500', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $expense = makeExpense($owner->tenant_id, ['submitted_by' => $owner->id]);
    $this->post(route('finance.expenses.submit', $expense));

    // The submitter approving their own claim.
    $this->from(route('finance.expenses.show', $expense))
        ->post(route('finance.expenses.approve', $expense))
        ->assertRedirect(route('finance.expenses.show', $expense));

    expect($expense->refresh()->status)->toBe(ExpenseStatus::PendingApproval);
});

test('editing a locked expense is refused at the route layer', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $checker = User::factory()->create(['tenant_id' => $owner->tenant_id]);

    $expense = app(ApproveExpenseAction::class)->handle(
        app(SubmitExpenseAction::class)->handle(makeExpense($owner->tenant_id), $owner), $checker
    );

    $this->get(route('finance.expenses.edit', $expense))->assertForbidden();
});

test('expense categories support full crud', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('finance.expense-categories.index'))->assertOk()->assertSee('لا توجد تصنيفات بعد.');

    $this->post(route('finance.expense-categories.store'), [
        'name' => 'سفر وانتقالات', 'code' => 'TRAVEL', 'is_active' => 1,
    ])->assertRedirect(route('finance.expense-categories.index'));

    $category = ExpenseCategory::query()->firstOrFail();
    expect($category->name)->toBe('سفر وانتقالات');

    $this->put(route('finance.expense-categories.update', $category), [
        'name' => 'سفر', 'code' => 'TRAVEL',
    ])->assertRedirect();

    expect($category->refresh()->name)->toBe('سفر')
        ->and($category->is_active)->toBeFalse();

    $this->delete(route('finance.expense-categories.destroy', $category))->assertRedirect();
    expect(ExpenseCategory::query()->count())->toBe(0);
});

test('category codes are unique per tenant', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $payload = ['name' => 'أ', 'code' => 'DUP'];
    $this->post(route('finance.expense-categories.store'), $payload)->assertRedirect();
    $this->post(route('finance.expense-categories.store'), $payload)->assertSessionHasErrors('code');
});

test('an employee cannot reach any expense route', function () {
    $employee = actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->actingAs($employee);
    $this->get(route('finance.expenses.index'))->assertForbidden();
    $this->get(route('finance.expenses.create'))->assertForbidden();
    $this->get(route('finance.expense-categories.index'))->assertForbidden();
});

test('expenses are invisible across tenants', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $expense = makeExpense($owner->tenant_id);

    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect(Expense::query()->count())->toBe(0);
    $this->get(route('finance.expenses.show', $expense->id))->assertNotFound();
});
