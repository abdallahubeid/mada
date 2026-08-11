<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\JobApplication;
use Illuminate\Support\Facades\Route;

class NewJobApplicationNotification extends TenantNotification
{
    public function __construct(public JobApplication $application)
    {
        $this->application->loadMissing(['jobPosting']);
    }

    protected function title(): string
    {
        return 'طلب توظيف جديد';
    }

    protected function message(): string
    {
        $applicant = $this->application->applicant_name;
        $job = $this->application->jobPosting?->title ?? 'وظيفة';

        return "تقدّم «{$applicant}» على وظيفة «{$job}» عبر بوابة التوظيف.";
    }

    protected function url(): ?string
    {
        if (Route::has('hr.applications.show')) {
            return route('hr.applications.show', $this->application);
        }

        return Route::has('hr.applications.index') ? route('hr.applications.index') : null;
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
        return 'application.received';
    }
}
