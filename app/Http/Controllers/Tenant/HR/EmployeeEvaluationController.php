<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use App\Domain\Tenancy\Enums\EvaluationStatus;
use App\Domain\Tenancy\Models\Department;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeEvaluation;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\EvaluationPublished;
use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\Tenancy\EvaluationPeriodCatalog;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class EmployeeEvaluationController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly EvaluationPeriodCatalog $periods,
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);
        abort_unless($this->canAccessEvaluations($user), 403);

        $viewerMode = $this->viewerMode($user);
        $defaultType = $this->defaultPeriodType();
        $periodType = EvaluationPeriodType::tryFrom((string) $request->string('period_type')) ?? $defaultType;
        $periodOptions = $this->periods->options($periodType);
        $requestedKey = (string) $request->string('period_key');
        $periodKey = $this->periods->isValidKey($periodType, $requestedKey)
            ? $requestedKey
            : $this->periods->currentKey($periodType);

        $employees = $this->employeesForViewer($user, $viewerMode);
        $evaluations = $this->evaluationsIndexed($employees, $periodType, $periodKey);
        $groups = $viewerMode === 'hr' || $viewerMode === 'owner'
            ? $this->groupByDepartment($employees)
            : collect([
                [
                    'department' => null,
                    'head' => null,
                    'employees' => $employees,
                ],
            ]);

        return view('tenant.hr.evaluations.index', [
            'viewerMode' => $viewerMode,
            'periodType' => $periodType,
            'periodKey' => $periodKey,
            'periodLabel' => $this->periods->label($periodType, $periodKey),
            'periodTypes' => EvaluationPeriodType::cases(),
            'periodOptions' => $periodOptions,
            'groups' => $groups,
            'evaluations' => $evaluations,
            'canSubmit' => $this->canSubmit($user),
            'canApprove' => $this->canApprove($user),
            'periodLocked' => $this->periodIsFullyApproved($employees, $periodType, $periodKey),
        ]);
    }

    public function myEvaluations(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $employee = $this->linkedEmployee($user);

        $evaluations = $employee === null
            ? collect()
            : EmployeeEvaluation::query()
                ->with('evaluator')
                ->where('employee_id', $employee->id)
                ->whereIn('status', [EvaluationStatus::Submitted, EvaluationStatus::Approved])
                ->orderByDesc('period_key')
                ->get()
                ->map(fn (EmployeeEvaluation $evaluation): array => [
                    'evaluation' => $evaluation,
                    'periodLabel' => $this->periods->label($evaluation->period_type, $evaluation->period_key),
                ]);

        return view('tenant.hr.employee.evaluations', [
            'employee' => $employee,
            'evaluations' => $evaluations,
        ]);
    }

    public function upsert(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canSubmit($user), 403);

        $data = $request->validate([
            'period_type' => ['required', Rule::in(EvaluationPeriodType::values())],
            'period_key' => ['required', 'string', 'max:32'],
            'rows' => ['required', 'array', 'min:1'],
            'rows.*.employee_id' => ['required', 'integer'],
            'rows.*.rating' => ['nullable', 'numeric', 'min:0', 'max:5'],
            'rows.*.notes' => ['nullable', 'string', 'max:5000'],
            'intent' => ['required', Rule::in(['draft', 'submit'])],
        ]);

        $periodType = EvaluationPeriodType::from($data['period_type']);
        abort_unless($this->periods->isValidKey($periodType, $data['period_key']), 422);

        $actorEmployee = $this->linkedEmployee($user);
        $allowedIds = $this->employeesForViewer($user, $this->viewerMode($user))->pluck('id')->all();

        /** @var list<EmployeeEvaluation> $published */
        $published = [];

        DB::transaction(function () use ($data, $periodType, $actorEmployee, $allowedIds, &$published): void {
            $status = $data['intent'] === 'submit'
                ? EvaluationStatus::Submitted
                : EvaluationStatus::Draft;

            foreach ($data['rows'] as $row) {
                $employeeId = (int) $row['employee_id'];
                if (! in_array($employeeId, $allowedIds, true)) {
                    continue;
                }

                $evaluation = EmployeeEvaluation::query()->firstOrNew([
                    'employee_id' => $employeeId,
                    'period_type' => $periodType,
                    'period_key' => $data['period_key'],
                ]);

                if ($evaluation->exists && $evaluation->isLocked()) {
                    continue;
                }

                $rating = $row['rating'] ?? null;
                $wasVisible = $evaluation->exists && $evaluation->status !== EvaluationStatus::Draft;

                $evaluation->fill([
                    'evaluator_id' => $actorEmployee?->id ?? $evaluation->evaluator_id,
                    'rating' => $rating === null || $rating === '' ? null : $rating,
                    'notes' => $row['notes'] ?? null,
                    'status' => ($status === EvaluationStatus::Submitted && $rating === null)
                        ? EvaluationStatus::Draft
                        : $status,
                ]);
                $evaluation->save();

                // Notify only on the draft -> visible transition; re-submitting an
                // already-visible evaluation should not re-notify the employee.
                if (! $wasVisible && $evaluation->status === EvaluationStatus::Submitted) {
                    $published[] = $evaluation;
                }
            }
        });

        foreach ($published as $evaluation) {
            event(new EvaluationPublished($evaluation, $user->id));
        }

        flash()->success($data['intent'] === 'submit'
            ? 'تم إرسال التقييمات بنجاح.'
            : 'تم حفظ المسودة بنجاح.');

        return redirect()->route('hr.evaluations.index', [
            'period_type' => $periodType->value,
            'period_key' => $data['period_key'],
        ]);
    }

    public function approve(Request $request): RedirectResponse
    {
        $user = $request->user();
        abort_unless($user instanceof User && $this->canApprove($user), 403);

        $data = $request->validate([
            'period_type' => ['required', Rule::in(EvaluationPeriodType::values())],
            'period_key' => ['required', 'string', 'max:32'],
        ]);

        $periodType = EvaluationPeriodType::from($data['period_type']);
        abort_unless($this->periods->isValidKey($periodType, $data['period_key']), 422);

        // Drafts become visible to the employee for the first time on approval;
        // already-Submitted rows were announced when they were submitted, so
        // capture the draft ids before the mass update overwrites the status.
        $newlyVisibleIds = EmployeeEvaluation::query()
            ->where('period_type', $periodType)
            ->where('period_key', $data['period_key'])
            ->where('status', EvaluationStatus::Draft)
            ->pluck('id');

        $updated = EmployeeEvaluation::query()
            ->where('period_type', $periodType)
            ->where('period_key', $data['period_key'])
            ->whereIn('status', [EvaluationStatus::Draft, EvaluationStatus::Submitted])
            ->update(['status' => EvaluationStatus::Approved]);

        EmployeeEvaluation::query()
            ->whereIn('id', $newlyVisibleIds)
            ->get()
            ->each(fn (EmployeeEvaluation $evaluation) => event(
                new EvaluationPublished($evaluation, $user->id),
            ));

        flash()->success($updated > 0
            ? "تم اعتماد تقييمات الفترة ({$updated} سجل)."
            : 'لا توجد تقييمات للاعتماد في هذه الفترة.');

        return redirect()->route('hr.evaluations.index', [
            'period_type' => $periodType->value,
            'period_key' => $data['period_key'],
        ]);
    }

    private function canAccessEvaluations(User $user): bool
    {
        return $this->canSubmit($user)
            || $this->canApprove($user)
            || $user->can('hr.evaluations.view_any')
            || $this->hasDirectReports($user);
    }

    private function canSubmit(User $user): bool
    {
        return $user->can('hr.evaluations.manage') || $this->hasDirectReports($user);
    }

    private function canApprove(User $user): bool
    {
        return $user->can('hr.evaluations.approve') || $user->isOwner();
    }

    /**
     * @return 'owner'|'hr'|'manager'
     */
    private function viewerMode(User $user): string
    {
        if ($this->canApprove($user)) {
            return 'owner';
        }

        if ($user->can('hr.evaluations.view_any') || $user->can('hr.evaluations.manage')) {
            return 'hr';
        }

        return 'manager';
    }

    private function defaultPeriodType(): EvaluationPeriodType
    {
        $settings = OrgSetting::query()->first();
        $value = $settings?->evaluation_periodicity;

        if ($value instanceof EvaluationPeriodType) {
            return $value;
        }

        return EvaluationPeriodType::tryFrom((string) ($value ?? ''))
            ?? EvaluationPeriodType::Quarterly;
    }

    private function linkedEmployee(User $user): ?Employee
    {
        return Employee::query()->where('user_id', $user->id)->first();
    }

    private function hasDirectReports(User $user): bool
    {
        $employee = $this->linkedEmployee($user);

        if ($employee === null) {
            return false;
        }

        return Employee::query()->where('manager_id', $employee->id)->exists();
    }

    /**
     * @return Collection<int, Employee>
     */
    private function employeesForViewer(User $user, string $viewerMode): Collection
    {
        if ($viewerMode === 'manager') {
            $manager = $this->linkedEmployee($user);
            abort_unless($manager !== null, 403);

            return Employee::query()
                ->with(['department', 'manager'])
                ->where('manager_id', $manager->id)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();
        }

        return Employee::query()
            ->with(['department.head', 'manager'])
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->get();
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, EmployeeEvaluation>
     */
    private function evaluationsIndexed(Collection $employees, EvaluationPeriodType $periodType, string $periodKey): Collection
    {
        if ($employees->isEmpty()) {
            return collect();
        }

        return EmployeeEvaluation::query()
            ->whereIn('employee_id', $employees->modelKeys())
            ->where('period_type', $periodType)
            ->where('period_key', $periodKey)
            ->get()
            ->keyBy('employee_id');
    }

    /**
     * @param  Collection<int, Employee>  $employees
     * @return Collection<int, array{department: ?Department, head: ?Employee, employees: Collection<int, Employee>}>
     */
    private function groupByDepartment(Collection $employees): Collection
    {
        $departments = Department::query()
            ->with('head')
            ->orderBy('name')
            ->get()
            ->keyBy('id');

        $grouped = $employees
            ->groupBy(fn (Employee $employee): int => (int) ($employee->department_id ?? 0))
            ->map(function (Collection $members, int $departmentId) use ($departments): array {
                $department = $departmentId > 0 ? $departments->get($departmentId) : null;
                $head = $department?->head;

                $ordered = $members->sortBy(function (Employee $employee) use ($head): string {
                    $isHead = $head && (int) $employee->id === (int) $head->id;

                    return ($isHead ? '0-' : '1-').$employee->full_name;
                })->values();

                return [
                    'department' => $department,
                    'head' => $head,
                    'employees' => $ordered,
                ];
            })
            ->sortBy(fn (array $group): string => $group['department']?->name ?? 'zzz')
            ->values();

        return $grouped;
    }

    /**
     * @param  Collection<int, Employee>  $employees
     */
    private function periodIsFullyApproved(Collection $employees, EvaluationPeriodType $periodType, string $periodKey): bool
    {
        if ($employees->isEmpty()) {
            return false;
        }

        $count = EmployeeEvaluation::query()
            ->whereIn('employee_id', $employees->modelKeys())
            ->where('period_type', $periodType)
            ->where('period_key', $periodKey)
            ->count();

        if ($count === 0) {
            return false;
        }

        return ! EmployeeEvaluation::query()
            ->whereIn('employee_id', $employees->modelKeys())
            ->where('period_type', $periodType)
            ->where('period_key', $periodKey)
            ->where('status', '!=', EvaluationStatus::Approved)
            ->exists();
    }
}
