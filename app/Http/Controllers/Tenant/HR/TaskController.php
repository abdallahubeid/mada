<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Enums\TaskPriority;
use App\Domain\Tenancy\Enums\TaskStatus;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\Task;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\TaskAssigned;
use App\Events\Tenancy\TaskCompleted;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreTaskRequest;
use App\Http\Requests\Tenant\UpdateTaskStatusRequest;
use App\Models\User;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TaskController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $manager = $this->linkedEmployee($user);

        $directReports = $manager === null
            ? collect()
            : Employee::query()
                ->where('manager_id', $manager->id)
                ->orderBy('first_name')
                ->orderBy('last_name')
                ->get();

        $tasks = $manager === null
            ? collect()
            : Task::query()
                ->with('employee')
                ->where('manager_id', $manager->id)
                ->orderByDesc('created_at')
                ->get();

        return view('tenant.hr.tasks.index', [
            'manager' => $manager,
            'directReports' => $directReports,
            'tasks' => $tasks,
            'priorities' => TaskPriority::cases(),
        ]);
    }

    public function store(StoreTaskRequest $request): RedirectResponse
    {
        $data = $request->validated();

        $manager = $this->linkedEmployee($request->user());
        abort_unless($manager !== null, 403);

        $task = Task::query()->create([
            'manager_id' => $manager->id,
            'employee_id' => $data['employee_id'],
            'title' => $data['title'],
            'description' => $data['description'] ?? null,
            'due_date' => $data['due_date'] ?? null,
            'priority' => $data['priority'] ?? TaskPriority::Medium->value,
            'status' => TaskStatus::Todo->value,
        ]);

        event(new TaskAssigned($task->fresh(), $request->user()?->id));

        flash()->success('تم إسناد المهمة بنجاح.');

        return redirect()->route('hr.tasks.index');
    }

    public function myTasks(Request $request): View
    {
        $user = $request->user();
        abort_unless($user instanceof User, 403);

        $employee = $this->linkedEmployee($user);

        $tasks = $employee === null
            ? collect()
            : Task::query()
                ->with('manager')
                ->where('employee_id', $employee->id)
                ->orderByDesc('due_date')
                ->get();

        /** @var Collection<string, Collection<int, Task>> $columns */
        $columns = collect(TaskStatus::cases())
            ->mapWithKeys(fn (TaskStatus $status): array => [
                $status->value => $tasks->filter(fn (Task $task): bool => $task->status === $status)->values(),
            ]);

        return view('tenant.hr.employee.tasks', [
            'employee' => $employee,
            'columns' => $columns,
            'statuses' => TaskStatus::cases(),
        ]);
    }

    public function updateStatus(UpdateTaskStatusRequest $request, Task $task): RedirectResponse
    {
        $this->ensureTenantTask($task);

        $employee = $this->linkedEmployee($request->user());
        abort_unless($employee !== null && (int) $task->employee_id === (int) $employee->id, 403);

        $previousStatus = $task->status;
        $task->update(['status' => $request->validated('status')]);
        $task->refresh();

        // Only on the transition into Completed — re-submitting the same status
        // should not re-notify the manager.
        if ($task->status === TaskStatus::Completed && $previousStatus !== TaskStatus::Completed) {
            event(new TaskCompleted($task, $request->user()?->id));
        }

        flash()->success('تم تحديث حالة المهمة.');

        return redirect()->route('tenant.hr.my-tasks');
    }

    private function linkedEmployee(?User $user): ?Employee
    {
        if ($user === null) {
            return null;
        }

        return Employee::query()->where('user_id', $user->id)->first();
    }

    private function ensureTenantTask(Task $task): void
    {
        abort_unless(
            (int) $task->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
