<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Employee;
use Illuminate\Support\Facades\Route;

class EmployeeCreatedNotification extends TenantNotification
{
    public function __construct(public Employee $employee) {}

    protected function title(): string
    {
        return 'موظف جديد';
    }

    protected function message(): string
    {
        return "أُضيف «{$this->employee->full_name}» ({$this->employee->job_title}) إلى المؤسسة.";
    }

    protected function url(): ?string
    {
        return Route::has('hr.employees.show')
            ? route('hr.employees.show', $this->employee)
            : null;
    }

    protected function icon(): string
    {
        return 'employee';
    }

    protected function severity(): string
    {
        return 'medium';
    }

    protected function type(): string
    {
        return 'employee.created';
    }
}
