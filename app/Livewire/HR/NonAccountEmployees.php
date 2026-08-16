<?php

namespace App\Livewire\HR;

use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Mail\Tenancy\EmployeeWelcomeMail;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Layout;
use Livewire\Attributes\Title;
use Livewire\Attributes\Url;
use Livewire\Component;
use Livewire\WithPagination;

/**
 * Employees with no login, and the per-row actions that give them one.
 *
 * ─────────────────────────────────────────────────────────────────────────
 * ONE EMPLOYEE AT A TIME, DELIBERATELY
 *
 * This screen previously carried checkboxes and a bulk "create accounts for
 * everyone selected" button. That is gone. Account creation now needs a
 * decision per person — chiefly the email address, which is the one field
 * that cannot be guessed and the one that makes the invite land or bounce.
 *
 * A bulk action over a field the operator has to supply per row is a bulk
 * action that silently skips most of its input, so the flow is now: open a
 * modal, confirm or type the address, create the one account.
 * ─────────────────────────────────────────────────────────────────────────
 */
#[Layout('components.layouts.app')]
#[Title('موظفون بلا حسابات')]
class NonAccountEmployees extends Component
{
    use WithPagination;

    #[Url(as: 'q', keep: false)]
    public string $search = '';

    /** Employee currently open in the details drawer. */
    public ?int $viewingId = null;

    /** Employee currently open in the create-account modal. */
    public ?int $creatingId = null;

    /** Editable address for the account being created. */
    public string $accountEmail = '';

    /** Role granted to the new account. */
    public string $accountRole = TenantPermissionCatalog::ROLE_EMPLOYEE;

    public function updatedSearch(): void
    {
        $this->resetPage();
    }

    /**
     * @return LengthAwarePaginator<int, Employee>
     */
    public function rows(): LengthAwarePaginator
    {
        return Employee::query()
            ->with('department')
            ->whereNull('user_id')
            ->where('tenant_id', $this->tenantId())
            ->when($this->search !== '', function ($q): void {
                $term = '%'.$this->search.'%';
                $q->where(function ($inner) use ($term): void {
                    $inner->where('first_name', 'like', $term)
                        ->orWhere('last_name', 'like', $term)
                        ->orWhere('email', 'like', $term)
                        ->orWhere('job_title', 'like', $term);
                });
            })
            ->latest('id')
            ->paginate(15);
    }

    public function viewDetails(int $employeeId): void
    {
        $employee = $this->resolve($employeeId);

        if ($employee === null) {
            $this->unavailable();

            return;
        }

        $this->viewingId = $employee->id;
    }

    public function closeDetails(): void
    {
        $this->viewingId = null;
    }

    /**
     * Open the create-account modal, pre-filling the address when we have one.
     */
    public function startCreate(int $employeeId): void
    {
        $employee = $this->resolve($employeeId);

        if ($employee === null) {
            $this->unavailable();

            return;
        }

        $this->creatingId = $employee->id;
        $this->accountEmail = (string) $employee->email;
        $this->accountRole = TenantPermissionCatalog::ROLE_EMPLOYEE;
        $this->resetValidation();
    }

    /**
     * One refusal path for every row action.
     *
     * Deliberately vague: the same message covers "belongs to another tenant",
     * "already has a login" and "was deleted in another tab". Distinguishing
     * them would confirm to a caller probing ids which employees exist
     * elsewhere, and none of the three is separately actionable by the
     * operator — the row simply is not available to them.
     */
    private function unavailable(): void
    {
        $this->viewingId = null;
        $this->creatingId = null;

        $this->dispatch('toast', type: 'warning', message: 'هذا الموظف لم يعد متاحاً. حدّث الصفحة وحاول مجدداً.');
    }

    public function closeCreate(): void
    {
        $this->creatingId = null;
        $this->accountEmail = '';
        $this->resetValidation();
    }

    /**
     * Create the account for the employee in the modal.
     *
     * The address is validated as unique against `users` BEFORE anything is
     * written, so the operator gets an inline field error rather than a failed
     * batch summary. The DB unique index still backs it — validation is the
     * message, the constraint is the guarantee.
     */
    public function createAccount(): void
    {
        $this->authorize('create', Employee::class);

        $employee = $this->resolve((int) $this->creatingId);

        /*
         * Re-resolved on submit, not trusted from when the modal opened. The
         * employee may have been given an account in another tab between the
         * two requests, and `creatingId` is client-supplied besides.
         */
        if ($employee === null) {
            $this->unavailable();

            return;
        }

        $validated = $this->validate([
            'accountEmail' => ['required', 'email:rfc', 'max:255', Rule::unique('users', 'email')],
            'accountRole' => ['required', Rule::in(array_keys(TenantPermissionCatalog::roleLabels()))],
        ], [], [
            'accountEmail' => 'البريد الإلكتروني',
            'accountRole' => 'الدور',
        ]);

        $email = strtolower(trim($validated['accountEmail']));
        $plainPassword = Str::password(12);
        $tenantId = $this->tenantId();

        try {
            $user = DB::transaction(function () use ($employee, $email, $plainPassword, $tenantId, $validated): User {
                $user = User::query()->create([
                    'tenant_id' => $tenantId,
                    'department_id' => $employee->department_id,
                    'name' => $employee->full_name,
                    'email' => $email,
                    'password' => $plainPassword,
                    'phone' => $employee->phone,
                    'job_title' => $employee->job_title,
                    'is_active' => true,
                ]);

                $user->syncRoles([$validated['accountRole']]);

                /*
                 * The employee row keeps the address too. Without this the
                 * operator's typing is lost the moment the modal closes, and
                 * the next screen that needs to contact this person has
                 * nothing again.
                 */
                $employee->forceFill([
                    'user_id' => $user->id,
                    'email' => $email,
                ])->save();

                return $user;
            });
        } catch (\Throwable $e) {
            Log::warning('Account creation failed', [
                'employee_id' => $employee->id,
                'error' => $e->getMessage(),
            ]);

            $this->addError('accountEmail', 'تعذّر إنشاء الحساب. حاول مرة أخرى.');

            return;
        }

        // Outside the transaction: a mail failure must not undo a good account.
        try {
            $tenant = app(TenantContext::class)->getTenant();

            if ($tenant !== null) {
                Mail::to($user->email)->send(new EmployeeWelcomeMail(
                    $user,
                    $tenant,
                    $plainPassword,
                    TenantPermissionCatalog::roleLabels()[$validated['accountRole']],
                ));
            }
        } catch (\Throwable $e) {
            Log::warning('Welcome mail failed after account creation', [
                'user_id' => $user->id,
                'error' => $e->getMessage(),
            ]);

            $this->closeCreate();
            $this->dispatch('toast', type: 'warning', message: 'تم إنشاء الحساب، لكن تعذّر إرسال البريد الترحيبي.');

            return;
        }

        $this->closeCreate();
        $this->dispatch('toast', type: 'success', message: "تم إنشاء حساب {$user->name} وإرسال بيانات الدخول.");
    }

    /**
     * Fetch an employee, scoped to the tenant and to "has no account".
     *
     * Every action funnels through here rather than trusting the id in the
     * payload: a Livewire property is client-supplied, so an id from another
     * tenant — or one that already has a login — must not be actionable.
     *
     * Returns NULL rather than throwing. `findOrFail` here surfaced as the
     * app's full-page 404 overlay on top of a working screen, which reads as
     * a crash rather than as a refusal.
     */
    private function resolve(int $employeeId): ?Employee
    {
        $tenantId = $this->tenantId();

        if ($tenantId === null) {
            return null;
        }

        return Employee::query()
            ->whereNull('user_id')
            ->where('tenant_id', $tenantId)
            ->find($employeeId);
    }

    /**
     * The active tenant id.
     *
     * ─────────────────────────────────────────────────────────────────────
     * WHY THIS DOES NOT RELY ON TenantContext ALONE
     *
     * `TenantContext` is populated by the `tenant.context` middleware, which
     * is attached to the tenant route group. Livewire's own `POST
     * livewire/update` endpoint is registered by the package OUTSIDE that
     * group and runs with `['web']` only — so on every Livewire action the
     * context is EMPTY, `getTenantId()` returns null, and a query scoped to
     * `where('tenant_id', null)` matches nothing.
     *
     * That is what produced the 404 on both row buttons: not a seeded-data
     * mismatch, but a tenant id that does not exist on that request at all.
     *
     * The authenticated user is session-backed and therefore always present,
     * so their `tenant_id` is the reliable source here. The context is still
     * preferred when set — it is authoritative for impersonation and for the
     * platform-operator path — with the user as the fallback.
     *
     * Isolation is unchanged: the query is still scoped, it just now scopes
     * to a value that exists.
     * ─────────────────────────────────────────────────────────────────────
     */
    private function tenantId(): ?int
    {
        $fromContext = app(TenantContext::class)->getTenantId();

        if ($fromContext !== null) {
            return $fromContext;
        }

        return auth()->user()?->tenant_id;
    }

    public function render()
    {
        return view('livewire.hr.non-account-employees', [
            'rows' => $this->rows(),
            'viewing' => $this->viewingId !== null
                ? Employee::query()->with('department')->find($this->viewingId)
                : null,
            'creating' => $this->creatingId !== null
                ? Employee::query()->with('department')->find($this->creatingId)
                : null,
            'roles' => TenantPermissionCatalog::roleLabels(),
        ]);
    }
}
