<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Task;
use Illuminate\Support\Facades\Route;

class TaskCompletedNotification extends TenantNotification
{
    public function __construct(public Task $task)
    {
        $this->task->loadMissing('employee');
    }

    protected function title(): string
    {
        return 'تم إنجاز مهمة';
    }

    protected function message(): string
    {
        $employee = $this->task->employee?->full_name ?? 'موظف';

        return "أكمل «{$employee}» المهمة: {$this->task->title}.";
    }

    protected function url(): ?string
    {
        return Route::has('hr.tasks.index')
            ? route('hr.tasks.index')
            : null;
    }

    protected function icon(): string
    {
        return 'task';
    }

    protected function severity(): string
    {
        return 'low';
    }

    protected function type(): string
    {
        return 'task.completed';
    }
}
