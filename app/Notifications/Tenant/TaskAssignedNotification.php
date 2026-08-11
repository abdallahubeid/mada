<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Task;
use Illuminate\Support\Facades\Route;

class TaskAssignedNotification extends TenantNotification
{
    public function __construct(public Task $task)
    {
        $this->task->loadMissing('manager');
    }

    protected function title(): string
    {
        return 'مهمة جديدة مُسندة إليك';
    }

    protected function message(): string
    {
        $manager = $this->task->manager?->full_name ?? 'مديرك المباشر';
        $due = $this->task->due_date?->format('Y-m-d');

        return $due === null
            ? "أسند إليك «{$manager}» مهمة: {$this->task->title}."
            : "أسند إليك «{$manager}» مهمة: {$this->task->title} — تستحق في {$due}.";
    }

    protected function url(): ?string
    {
        return Route::has('tenant.hr.my-tasks')
            ? route('tenant.hr.my-tasks')
            : null;
    }

    protected function icon(): string
    {
        return 'task';
    }

    protected function severity(): string
    {
        return $this->task->priority->value === 'high' ? 'high' : 'medium';
    }

    protected function type(): string
    {
        return 'task.assigned';
    }
}
