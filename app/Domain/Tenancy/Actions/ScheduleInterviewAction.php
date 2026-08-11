<?php

namespace App\Domain\Tenancy\Actions;

use App\Domain\Tenancy\Enums\ApplicationStatus;
use App\Domain\Tenancy\Models\Interview;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Support\InterviewMessageTemplate;
use App\Mail\Tenant\CandidateInterviewMail;
use App\Models\User;
use App\Notifications\Tenant\InterviewScheduledNotification;
use App\Services\Tenancy\TenantAuditor;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Schedules one interview stage and dispatches the invitation.
 *
 * Persistence runs in a transaction; the email, the in-app notification and the
 * audit entry run AFTER it commits. Sending mail inside a transaction means a
 * later rollback leaves a candidate holding an invitation to a meeting that does
 * not exist — the one failure mode this ordering exists to prevent.
 */
final class ScheduleInterviewAction
{
    public function __construct(
        private readonly TenantNotifier $notifier,
        private readonly TenantAuditor $auditor,
    ) {}

    /**
     * @param  array{interviewer_id: int, scheduled_at: string, location_or_link: ?string, notes: ?string, email_subject: string, email_body: string, cc: list<string>}  $data
     */
    public function handle(JobApplication $application, array $data, ?User $author = null): Interview
    {
        $application->loadMissing('jobPosting');

        $scheduledAt = Carbon::parse($data['scheduled_at']);

        $interview = DB::transaction(function () use ($application, $data, $scheduledAt, $author): Interview {
            $interview = Interview::query()->create([
                'job_application_id' => $application->id,
                'interviewer_id' => $data['interviewer_id'],
                'scheduled_at' => $scheduledAt,
                'location_or_link' => $data['location_or_link'] ?? null,
                'notes' => $data['notes'] ?? null,
                'created_by' => $author?->id,
            ]);

            $this->advanceApplicationStatus($application);

            return $interview;
        });

        $tokens = InterviewMessageTemplate::tokens(
            $application,
            $scheduledAt,
            $data['location_or_link'] ?? null,
        );

        $subject = InterviewMessageTemplate::render($data['email_subject'], $tokens);
        $body = InterviewMessageTemplate::render($data['email_body'], $tokens);

        /*
         * The recipient is ALWAYS the application's own email. It is never read
         * from the request: a "To" the operator can edit turns this screen into
         * a way to send arbitrary mail from the tenant's domain.
         */
        Mail::to($application->email)
            ->cc($data['cc'] ?? [])
            ->send(new CandidateInterviewMail($subject, $body));

        $this->notifyInterviewer($interview, $application);

        $this->auditor->log('interview.scheduled', 'recruitment', $interview, [
            'job_application_id' => $application->id,
            'candidate_email' => $application->email,
            'interviewer_id' => $interview->interviewer_id,
            'scheduled_at' => $scheduledAt->toIso8601String(),
            'location_or_link' => $interview->location_or_link,
            'cc' => $data['cc'] ?? [],
            // The rendered subject is the auditable fact about what was sent.
            // The body is deliberately not copied here — it can run to
            // thousands of characters and would bloat every audit row.
            'email_subject' => $subject,
        ]);

        return $interview;
    }

    /**
     * Move the application into the interview stage.
     *
     * Deliberately one-way: an application already `accepted` or `rejected` has
     * reached a decision, and a follow-up interview must not silently walk that
     * decision backwards to `interviewed`. Scheduling still succeeds — only the
     * status is left alone.
     */
    private function advanceApplicationStatus(JobApplication $application): void
    {
        $terminal = [ApplicationStatus::Accepted, ApplicationStatus::Rejected];

        if (in_array($application->status, $terminal, true)) {
            return;
        }

        if ($application->status === ApplicationStatus::Interviewed) {
            return;
        }

        $application->update(['status' => ApplicationStatus::Interviewed]);
    }

    private function notifyInterviewer(Interview $interview, JobApplication $application): void
    {
        $interviewer = User::query()
            ->where('tenant_id', $application->tenant_id)
            ->whereKey($interview->interviewer_id)
            ->get();

        if ($interviewer->isEmpty()) {
            return;
        }

        $this->notifier->toUsers($interviewer, new InterviewScheduledNotification($interview));
    }
}
