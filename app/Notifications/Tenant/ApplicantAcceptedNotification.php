<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\JobApplication;
use Illuminate\Support\Facades\Route;

class ApplicantAcceptedNotification extends TenantNotification
{
    public function __construct(
        public JobApplication $application,
        public Employee $employee,
    ) {
        $this->application->loadMissing(['jobPosting']);
    }

    protected function title(): string
    {
        return 'تحويل متقدم إلى موظف';
    }

    protected function message(): string
    {
        $job = $this->application->jobPosting?->title ?? 'وظيفة';

        return "تم تحويل «{$this->application->applicant_name}» ({$job}) إلى موظف نشط.";
    }

    protected function url(): ?string
    {
        return Route::has('hr.employees.show')
            ? route('hr.employees.show', $this->employee)
            : null;
    }

    protected function icon(): string
    {
        return 'recruitment';
    }

    protected function severity(): string
    {
        return 'high';
    }

    protected function type(): string
    {
        return 'application.converted';
    }
}
