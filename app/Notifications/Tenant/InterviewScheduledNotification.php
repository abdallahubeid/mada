<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Interview;
use App\Domain\Tenancy\Support\InterviewMessageTemplate;
use Illuminate\Support\Facades\Route;

/**
 * Tells the selected interviewer that a candidate meeting is on their calendar.
 */
class InterviewScheduledNotification extends TenantNotification
{
    public function __construct(public Interview $interview)
    {
        $this->interview->loadMissing('jobApplication.jobPosting');
    }

    protected function title(): string
    {
        return 'مقابلة مرشّح مُسندة إليك';
    }

    protected function message(): string
    {
        $application = $this->interview->jobApplication;
        $candidate = $application?->applicant_name ?? 'مرشّح';
        $job = $application?->jobPosting?->title;
        $when = InterviewMessageTemplate::formatDate($this->interview->scheduled_at);

        return $job === null
            ? "مقابلة مع «{$candidate}» — {$when}."
            : "مقابلة مع «{$candidate}» لوظيفة {$job} — {$when}.";
    }

    protected function url(): ?string
    {
        return Route::has('hr.applications.show') && $this->interview->job_application_id !== null
            ? route('hr.applications.show', $this->interview->job_application_id)
            : null;
    }

    protected function icon(): string
    {
        return 'calendar';
    }

    protected function severity(): string
    {
        return 'high';
    }

    protected function type(): string
    {
        return 'interview.scheduled';
    }
}
