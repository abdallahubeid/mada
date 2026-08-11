<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Models\Employee;
use Illuminate\Support\Facades\Route;

class EmployeeStatusChangedNotification extends TenantNotification
{
    public function __construct(
        public Employee $employee,
        public ?EmployeeStatus $previousStatus,
        public ?EmployeeStatus $newStatus,
        public bool $deleted = false,
    ) {}

    protected function title(): string
    {
        return $this->deleted ? 'حذف موظف' : 'تغيير حالة موظف';
    }

    protected function message(): string
    {
        $name = $this->employee->full_name;

        if ($this->deleted) {
            return "تم حذف ملف الموظف «{$name}».";
        }

        $from = $this->previousStatus?->label() ?? '—';
        $to = $this->newStatus?->label() ?? '—';

        return "تم نقل «{$name}» من {$from} إلى {$to}.";
    }

    protected function url(): ?string
    {
        if ($this->deleted || ! Route::has('hr.employees.show')) {
            return Route::has('hr.employees.index') ? route('hr.employees.index') : null;
        }

        return route('hr.employees.show', $this->employee);
    }

    protected function icon(): string
    {
        return 'employee';
    }

    protected function severity(): string
    {
        return 'high';
    }

    protected function type(): string
    {
        return $this->deleted ? 'employee.deleted' : 'employee.status_changed';
    }
}
