<?php

namespace App\Http\Controllers\Tenant\HR;

use App\Domain\Tenancy\Actions\ScheduleInterviewAction;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Support\InterviewMessageTemplate;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\ScheduleInterviewRequest;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;

class InterviewController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function store(
        ScheduleInterviewRequest $request,
        JobApplication $application,
        ScheduleInterviewAction $action,
    ): RedirectResponse {
        $this->ensureTenantApplication($application);

        /** @var array{interviewer_id: int, scheduled_at: string, location_or_link: ?string, notes: ?string, email_subject: string, email_body: string, cc: list<string>} $data */
        $data = $request->validated();

        $action->handle($application, $data, $request->user());

        flash()->success('تم جدولة المقابلة وإرسال الدعوة إلى المرشّح.');

        return redirect()->route('hr.applications.show', $application);
    }

    /**
     * Server-side render of the invitation with tags replaced.
     *
     * Deliberately server-side rather than a JS string-replace: this calls the
     * exact renderer the mail uses, including the tenant-timezone date, so what
     * the reviewer approves is what the candidate receives. A client-side
     * preview would be a second implementation of the same rules.
     */
    public function preview(Request $request, JobApplication $application): JsonResponse
    {
        $this->ensureTenantApplication($application);
        $application->loadMissing('jobPosting');

        $validated = $request->validate([
            'scheduled_at' => ['nullable', 'date'],
            'location_or_link' => ['nullable', 'string', 'max:500'],
            'email_subject' => ['nullable', 'string', 'max:255'],
            'email_body' => ['nullable', 'string', 'max:20000'],
        ]);

        $tokens = InterviewMessageTemplate::tokens(
            $application,
            filled($validated['scheduled_at'] ?? null)
                ? Carbon::parse($validated['scheduled_at'])
                : Carbon::now(),
            $validated['location_or_link'] ?? null,
        );

        return response()->json([
            'to' => $application->email,
            'subject' => InterviewMessageTemplate::render(
                $validated['email_subject'] ?? InterviewMessageTemplate::defaultSubject(),
                $tokens,
            ),
            'body' => InterviewMessageTemplate::render(
                $validated['email_body'] ?? InterviewMessageTemplate::defaultBody(),
                $tokens,
            ),
        ]);
    }

    private function ensureTenantApplication(JobApplication $application): void
    {
        abort_unless(
            (int) $application->tenant_id === (int) $this->tenantContext->getTenantId(),
            404
        );
    }
}
