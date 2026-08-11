<?php

namespace App\Domain\Tenancy\Support;

use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\OrgSetting;
use Illuminate\Support\Carbon;

/**
 * Renders the HR-authored interview invitation, substituting template tags.
 *
 * Pure and static on purpose: the SAME method must serve both the preview
 * endpoint and the outgoing mail. A preview produced by different code than the
 * send is not a preview — it is a second implementation that will eventually
 * disagree with the message the candidate actually receives.
 *
 * Unrecognised tags are left verbatim rather than blanked, so a typo like
 * `{candidate-name}` shows up in the preview as itself instead of silently
 * vanishing from the email.
 */
final class InterviewMessageTemplate
{
    public const TAG_CANDIDATE_NAME = '{candidate_name}';

    public const TAG_JOB_TITLE = '{job_title}';

    public const TAG_INTERVIEW_DATE = '{interview_date}';

    public const TAG_LOCATION = '{location}';

    /**
     * Every tag the screen advertises, for the legend under the body field.
     *
     * @return list<string>
     */
    public static function tags(): array
    {
        return [
            self::TAG_CANDIDATE_NAME,
            self::TAG_JOB_TITLE,
            self::TAG_INTERVIEW_DATE,
            self::TAG_LOCATION,
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function tokens(
        JobApplication $application,
        Carbon $scheduledAt,
        ?string $locationOrLink,
    ): array {
        return [
            self::TAG_CANDIDATE_NAME => $application->applicant_name,
            self::TAG_JOB_TITLE => $application->jobPosting?->title ?? '—',
            self::TAG_INTERVIEW_DATE => self::formatDate($scheduledAt),
            self::TAG_LOCATION => filled($locationOrLink) ? $locationOrLink : 'سيتم تحديده لاحقاً',
        ];
    }

    /**
     * @param  array<string, string>  $tokens
     */
    public static function render(string $template, array $tokens): string
    {
        return strtr($template, $tokens);
    }

    /**
     * Presented to the candidate in the TENANT's timezone, not the server's.
     *
     * `scheduled_at` is stored UTC by the framework's datetime cast, so a
     * settlement scheduled for 14:30 Riyadh would otherwise be announced as
     * 11:30 to the candidate.
     */
    public static function formatDate(Carbon $scheduledAt): string
    {
        $timezone = OrgSetting::query()->value('timezone') ?? config('app.timezone', 'UTC');

        $local = $scheduledAt->copy()->timezone($timezone);

        return $local->format('Y-m-d').' الساعة '.$local->format('H:i');
    }

    public static function defaultSubject(): string
    {
        return 'دعوة لمقابلة وظيفة '.self::TAG_JOB_TITLE;
    }

    public static function defaultBody(): string
    {
        return <<<'TXT'
        مرحباً {candidate_name}،

        شكراً لتقدّمك لوظيفة {job_title}. يسعدنا دعوتك لإجراء مقابلة معنا.

        موعد المقابلة: {interview_date}
        المكان / رابط الاجتماع: {location}

        نرجو تأكيد حضورك بالرد على هذا البريد، وإحضار أي مستندات ترى أنها تدعم طلبك.

        بالتوفيق،
        فريق التوظيف
        TXT;
    }
}
