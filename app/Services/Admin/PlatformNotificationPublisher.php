<?php

namespace App\Services\Admin;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Events\Platform\PlatformNotificationCreated;
use App\Models\PlatformNotification;
use App\Models\SupportThread;
use App\Models\User;
use Illuminate\Support\Facades\Route;

/**
 * Persists every platform notification; optionally broadcasts urgent ones via Reverb.
 */
class PlatformNotificationPublisher
{
    /**
     * @param  array{category: string, title: string, body: string, target_url?: string|null, broadcast?: bool}  $payload
     */
    public function publish(array $payload): PlatformNotification
    {
        $notification = PlatformNotification::query()->create([
            'category' => $payload['category'],
            'title' => $payload['title'],
            'body' => $payload['body'],
            'target_url' => $payload['target_url'] ?? null,
            'read_at' => null,
        ]);

        if ($payload['broadcast'] ?? false) {
            event(new PlatformNotificationCreated(
                $notification,
                PlatformNotification::query()->unread()->count(),
            ));
        }

        return $notification;
    }

    public function tenantRegisteredPendingApproval(Tenant $tenant): PlatformNotification
    {
        return $this->publish([
            'category' => PlatformNotification::CATEGORY_APPROVAL,
            'title' => 'مستأجر جديد بانتظار المراجعة',
            // arabicLabel(), not label(): label() is the English enum name and
            // was rendering "Pending Approval" mid-sentence in an Arabic body.
            'body' => 'قدّمت «'.$tenant->name.'» طلب تسجيل ('.$tenant->status->arabicLabel().') وتنتظر مراجعتك.',
            'target_url' => Route::has('admin.tenants.show')
                ? route('admin.tenants.show', $tenant)
                : route('admin.tenants'),
            'broadcast' => true,
        ]);
    }

    public function tenantLifecycleChanged(Tenant $tenant, TenantStatus $from, TenantStatus $to): PlatformNotification
    {
        return $this->publish([
            'category' => PlatformNotification::CATEGORY_APPROVAL,
            'title' => 'تغيير حالة مستأجر',
            'body' => 'تم نقل «'.$tenant->name.'» من '.$from->arabicLabel().' إلى '.$to->arabicLabel().'.',
            'target_url' => Route::has('admin.tenants.show')
                ? route('admin.tenants.show', $tenant)
                : route('admin.tenants'),
            'broadcast' => true,
        ]);
    }

    public function securityAlert(string $title, string $body, ?string $targetUrl = null): PlatformNotification
    {
        return $this->publish([
            'category' => PlatformNotification::CATEGORY_SECURITY,
            'title' => $title,
            'body' => $body,
            'target_url' => $targetUrl ?? (Route::has('admin.account.security') ? route('admin.account.security') : null),
            'broadcast' => true,
        ]);
    }

    public function passwordChanged(User $user): PlatformNotification
    {
        return $this->securityAlert(
            'تغيير كلمة مرور مشرف',
            'تم تحديث كلمة مرور الحساب «'.$user->email.'».',
            Route::has('admin.audit-log') ? route('admin.audit-log') : null,
        );
    }

    public function jobFailed(string $jobName, string $exceptionMessage): PlatformNotification
    {
        return $this->publish([
            'category' => PlatformNotification::CATEGORY_JOB_FAILED,
            'title' => 'فشل تنفيذ مهمة خلفية',
            'body' => 'فشلت المهمة '.$jobName.' — '.mb_substr($exceptionMessage, 0, 240),
            'target_url' => null,
            'broadcast' => true,
        ]);
    }

    public function newSupportMessage(SupportThread $thread): PlatformNotification
    {
        return $this->publish([
            'category' => PlatformNotification::CATEGORY_OPS,
            'title' => 'رسالة دعم جديدة',
            'body' => '«'.$thread->subject.'» من '.($thread->company ?: $thread->name).' · '.$thread->email,
            'target_url' => route('admin.messages', [
                'status' => $thread->status,
                'thread' => $thread->id,
            ]),
            'broadcast' => true,
        ]);
    }

    public function newsletterSubscribed(string $email): PlatformNotification
    {
        return $this->publish([
            'category' => PlatformNotification::CATEGORY_OPS,
            'title' => 'اشتراك جديد في النشرة',
            'body' => 'انضم '.$email.' إلى قائمة النشرة البريدية.',
            'target_url' => route('admin.newsletter.index', ['q' => $email]),
            'broadcast' => false,
        ]);
    }

    public function newsletterCampaignCompleted(string $subject, int $sentCount): PlatformNotification
    {
        return $this->publish([
            'category' => PlatformNotification::CATEGORY_OPS,
            'title' => 'اكتملت حملة بريدية',
            'body' => 'أُرسلت «'.$subject.'» إلى '.$sentCount.' مشترك.',
            'target_url' => route('admin.newsletter.campaigns.index'),
            'broadcast' => false,
        ]);
    }
}
