<?php

use App\Domain\Tenancy\Enums\ApplicationStatus;
use App\Domain\Tenancy\Models\Interview;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Support\InterviewMessageTemplate;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Mail\Tenant\CandidateInterviewMail;
use App\Models\User;
use App\Notifications\Tenant\InterviewScheduledNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

/**
 * @return array{0: User, 1: JobApplication, 2: User}
 */
function recruitmentTenant(string $role = TenantPermissionCatalog::ROLE_OWNER): array
{
    $user = actingAsTenantUser($role, ['status' => 'active']);

    $posting = JobPosting::factory()->create([
        'tenant_id' => $user->tenant_id,
        'title' => 'مهندس برمجيات',
    ]);

    $application = JobApplication::factory()->create([
        'tenant_id' => $user->tenant_id,
        'job_posting_id' => $posting->id,
        'applicant_name' => 'سارة عبدالله',
        'email' => 'candidate@example.com',
        'status' => ApplicationStatus::UnderReview,
    ]);

    $interviewer = User::factory()->create([
        'tenant_id' => $user->tenant_id,
        'is_active' => true,
        'name' => 'خالد المحاور',
    ]);

    return [$user, $application, $interviewer];
}

/**
 * @return array<string, mixed>
 */
function interviewPayload(User $interviewer, array $overrides = []): array
{
    return array_replace([
        'interviewer_id' => $interviewer->id,
        'scheduled_at' => Carbon::now()->addWeek()->setTime(14, 30)->format('Y-m-d\TH:i'),
        'location_or_link' => 'مقر الشركة — الدور الثالث',
        'notes' => 'ملاحظة داخلية',
        'email_subject' => InterviewMessageTemplate::defaultSubject(),
        'email_body' => InterviewMessageTemplate::defaultBody(),
        'cc' => 'hr@example.com, manager@example.com',
    ], $overrides);
}

// ---------------------------------------------------------------------------
// Template rendering — preview and send share this code
// ---------------------------------------------------------------------------

test('every advertised tag is substituted', function () {
    [$user, $application] = recruitmentTenant();

    $tokens = InterviewMessageTemplate::tokens(
        $application->load('jobPosting'),
        Carbon::parse('2026-09-01 14:30'),
        'مقر الشركة',
    );

    $rendered = InterviewMessageTemplate::render(
        '{candidate_name} | {job_title} | {interview_date} | {location}',
        $tokens,
    );

    expect($rendered)->toContain('سارة عبدالله')
        ->and($rendered)->toContain('مهندس برمجيات')
        ->and($rendered)->toContain('مقر الشركة')
        ->and($rendered)->not->toContain('{candidate_name}')
        ->and($rendered)->not->toContain('{location}');
});

test('an unknown tag is left verbatim rather than blanked', function () {
    [, $application] = recruitmentTenant();

    $rendered = InterviewMessageTemplate::render(
        'مرحباً {candidate-name}',
        InterviewMessageTemplate::tokens($application->load('jobPosting'), Carbon::now(), null),
    );

    // A typo must be visible in the preview, not silently swallowed.
    expect($rendered)->toContain('{candidate-name}');
});

test('the interview date is rendered in the tenant timezone', function () {
    [$user, $application] = recruitmentTenant();

    OrgSetting::query()->create([
        'tenant_id' => $user->tenant_id,
        'timezone' => 'Asia/Riyadh',
    ]);

    // 11:30 UTC is 14:30 in Riyadh. Announcing the server's clock to a
    // candidate would put them three hours early.
    $formatted = InterviewMessageTemplate::formatDate(Carbon::parse('2026-09-01 11:30:00', 'UTC'));

    expect($formatted)->toContain('14:30')->and($formatted)->toContain('2026-09-01');
});

test('a missing location falls back to a placeholder instead of an empty line', function () {
    [, $application] = recruitmentTenant();

    $tokens = InterviewMessageTemplate::tokens($application->load('jobPosting'), Carbon::now(), null);

    expect($tokens[InterviewMessageTemplate::TAG_LOCATION])->toBe('سيتم تحديده لاحقاً');
});

// ---------------------------------------------------------------------------
// Scheduling
// ---------------------------------------------------------------------------

test('scheduling persists the interview, mails the candidate and notifies the interviewer', function () {
    Mail::fake();
    Notification::fake();

    [$user, $application, $interviewer] = recruitmentTenant();

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))
        ->assertRedirect(route('hr.applications.show', $application));

    $interview = Interview::query()->firstOrFail();

    expect($interview->job_application_id)->toBe($application->id)
        ->and($interview->interviewer_id)->toBe($interviewer->id)
        ->and($interview->tenant_id)->toBe($user->tenant_id)
        ->and($interview->created_by)->toBe($user->id)
        ->and($interview->location_or_link)->toBe('مقر الشركة — الدور الثالث')
        ->and($interview->notes)->toBe('ملاحظة داخلية');

    Mail::assertSent(CandidateInterviewMail::class, function (CandidateInterviewMail $mail): bool {
        return $mail->hasTo('candidate@example.com')
            && $mail->hasCc('hr@example.com')
            && $mail->hasCc('manager@example.com')
            // Tags must arrive substituted, never as literals.
            && str_contains($mail->subjectLine, 'مهندس برمجيات')
            && ! str_contains($mail->subjectLine, '{job_title}')
            && str_contains($mail->body, 'سارة عبدالله')
            && ! str_contains($mail->body, '{candidate_name}');
    });

    Notification::assertSentTo($interviewer, InterviewScheduledNotification::class);
});

test('scheduling moves the application into the interviewed stage', function () {
    Mail::fake();
    Notification::fake();

    [, $application, $interviewer] = recruitmentTenant();

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))
        ->assertRedirect();

    expect($application->refresh()->status)->toBe(ApplicationStatus::Interviewed);
});

test('a decided application keeps its status when a further interview is booked', function () {
    Mail::fake();
    Notification::fake();

    [, $application, $interviewer] = recruitmentTenant();
    $application->update(['status' => ApplicationStatus::Accepted]);

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))
        ->assertRedirect();

    /*
     * The interview is still booked — only the status is left alone. Walking an
     * accepted candidate back to "interviewed" would erase a decision someone
     * already made.
     */
    expect(Interview::query()->count())->toBe(1)
        ->and($application->refresh()->status)->toBe(ApplicationStatus::Accepted);
});

test('scheduling is written to the audit log', function () {
    Mail::fake();
    Notification::fake();

    [, $application, $interviewer] = recruitmentTenant();

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))
        ->assertRedirect();

    $log = DB::table('audit_logs')->where('action', 'interview.scheduled')->first();

    expect($log)->not->toBeNull()
        ->and($log->module)->toBe('recruitment');

    $changes = json_decode((string) $log->changes, true);

    expect($changes['candidate_email'])->toBe('candidate@example.com')
        ->and($changes['interviewer_id'])->toBe($interviewer->id)
        ->and($changes['cc'])->toBe(['hr@example.com', 'manager@example.com'])
        // The subject is stored rendered — the auditable fact is what was sent.
        ->and($changes['email_subject'])->toContain('مهندس برمجيات');
});

test('several interview stages can be booked against one candidate', function () {
    Mail::fake();
    Notification::fake();

    [, $application, $interviewer] = recruitmentTenant();

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))->assertRedirect();
    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer, [
        'scheduled_at' => Carbon::now()->addWeeks(2)->setTime(10, 0)->format('Y-m-d\TH:i'),
    ]))->assertRedirect();

    expect(Interview::query()->where('job_application_id', $application->id)->count())->toBe(2);
});

// ---------------------------------------------------------------------------
// Validation
// ---------------------------------------------------------------------------

test('the interviewer must belong to this tenant', function () {
    Mail::fake();

    [, $application] = recruitmentTenant();

    $outsider = User::factory()->create(['is_active' => true]);

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($outsider))
        ->assertSessionHasErrors('interviewer_id');

    expect(Interview::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('a past date, a missing subject and a malformed cc are refused', function () {
    Mail::fake();

    [, $application, $interviewer] = recruitmentTenant();

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer, [
        'scheduled_at' => Carbon::now()->subDay()->format('Y-m-d\TH:i'),
        'email_subject' => '',
        'cc' => 'not-an-email',
    ]))->assertSessionHasErrors(['scheduled_at', 'email_subject', 'cc.0']);

    expect(Interview::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('cc accepts several separators and drops blanks', function () {
    Mail::fake();
    Notification::fake();

    [, $application, $interviewer] = recruitmentTenant();

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer, [
        'cc' => "one@example.com;  two@example.com,\nthree@example.com,",
    ]))->assertRedirect();

    Mail::assertSent(
        CandidateInterviewMail::class,
        fn (CandidateInterviewMail $mail): bool => $mail->hasCc('one@example.com')
            && $mail->hasCc('two@example.com')
            && $mail->hasCc('three@example.com')
    );
});

// ---------------------------------------------------------------------------
// Preview
// ---------------------------------------------------------------------------

test('the preview returns the same substitution the mail will carry', function () {
    [, $application, $interviewer] = recruitmentTenant();

    $response = $this->postJson(route('hr.applications.interviews.preview', $application), [
        'scheduled_at' => '2026-09-01T14:30',
        'location_or_link' => 'الرياض',
        'email_subject' => 'مقابلة {job_title}',
        'email_body' => 'مرحباً {candidate_name}، موعدك {interview_date} في {location}.',
    ])->assertOk();

    expect($response->json('to'))->toBe('candidate@example.com')
        ->and($response->json('subject'))->toBe('مقابلة مهندس برمجيات')
        ->and($response->json('body'))->toContain('سارة عبدالله')
        ->and($response->json('body'))->toContain('الرياض')
        ->and($response->json('body'))->not->toContain('{interview_date}');
});

test('the preview never creates a record or sends anything', function () {
    Mail::fake();

    [, $application] = recruitmentTenant();

    $this->postJson(route('hr.applications.interviews.preview', $application), [
        'email_subject' => 'موضوع',
        'email_body' => 'نص',
    ])->assertOk();

    expect(Interview::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

// ---------------------------------------------------------------------------
// Authorization & isolation
// ---------------------------------------------------------------------------

test('an hr manager may schedule interviews', function () {
    Mail::fake();
    Notification::fake();

    [$owner, $application, $interviewer] = recruitmentTenant();

    $hrManager = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $hrManager->assignRole(TenantPermissionCatalog::ROLE_HR_MANAGER);

    $this->actingAs($hrManager)
        ->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))
        ->assertRedirect();

    expect(Interview::query()->count())->toBe(1);
});

test('an employee cannot schedule an interview or reach the preview', function () {
    Mail::fake();

    [$owner, $application, $interviewer] = recruitmentTenant();

    $employee = User::factory()->create(['tenant_id' => $owner->tenant_id]);
    $employee->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);
    $this->actingAs($employee);

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))
        ->assertForbidden();

    // The preview echoes the candidate's address and the composed message, so
    // it is gated exactly like the send.
    $this->postJson(route('hr.applications.interviews.preview', $application), [])
        ->assertForbidden();

    expect(Interview::query()->count())->toBe(0);
    Mail::assertNothingSent();
});

test('interviews are invisible and unreachable across tenants', function () {
    Mail::fake();
    Notification::fake();

    [, $application, $interviewer] = recruitmentTenant();

    $this->post(route('hr.applications.interviews.store', $application), interviewPayload($interviewer))->assertRedirect();

    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect(Interview::query()->count())->toBe(0);

    $this->post(route('hr.applications.interviews.store', $application->id), interviewPayload($interviewer))
        ->assertNotFound();
});

test('the scheduling modal renders on the candidate page for a recruiter', function () {
    [, $application] = recruitmentTenant();

    $this->get(route('hr.applications.show', $application))
        ->assertOk()
        ->assertSee('جدولة مقابلة')
        ->assertSee('{candidate_name}', escape: false);
});
