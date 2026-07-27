<?php

namespace App\Services\Newsletter;

use App\Mail\Marketing\NewsletterCampaignMail;
use App\Mail\Marketing\WelcomeNewsletterMail;
use App\Models\NewsletterCampaign;
use App\Models\NewsletterSubscriber;
use App\Services\Admin\PlatformNotificationPublisher;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;

/**
 * Subscribe / unsubscribe / campaign dispatch for the newsletter module.
 */
class NewsletterService
{
    public function __construct(private PlatformNotificationPublisher $notifications) {}

    /**
     * @return array{subscriber: NewsletterSubscriber, created: bool, already_subscribed: bool}
     */
    public function subscribe(string $email): array
    {
        $normalized = strtolower(trim($email));

        $subscriber = NewsletterSubscriber::withTrashed()
            ->where('email', $normalized)
            ->first();

        if ($subscriber !== null) {
            if ($subscriber->trashed()) {
                $subscriber->restore();
            }

            if ($subscriber->isSubscribed()) {
                return [
                    'subscriber' => $subscriber,
                    'created' => false,
                    'already_subscribed' => true,
                ];
            }

            $subscriber->markSubscribed();

            Mail::to($subscriber->email)->send(new WelcomeNewsletterMail($subscriber));
            $this->notifications->newsletterSubscribed($subscriber->email);

            return [
                'subscriber' => $subscriber->fresh() ?? $subscriber,
                'created' => false,
                'already_subscribed' => false,
            ];
        }

        $subscriber = NewsletterSubscriber::query()->create([
            'email' => $normalized,
            'status' => NewsletterSubscriber::STATUS_SUBSCRIBED,
            'subscribed_at' => now(),
            'unsubscribed_at' => null,
        ]);

        Mail::to($subscriber->email)->send(new WelcomeNewsletterMail($subscriber));
        $this->notifications->newsletterSubscribed($subscriber->email);

        return [
            'subscriber' => $subscriber,
            'created' => true,
            'already_subscribed' => false,
        ];
    }

    public function unsubscribeByEmail(string $email): ?NewsletterSubscriber
    {
        $subscriber = NewsletterSubscriber::query()
            ->where('email', strtolower(trim($email)))
            ->first();

        if ($subscriber === null) {
            return null;
        }

        if ($subscriber->isSubscribed()) {
            $subscriber->markUnsubscribed();
        }

        return $subscriber->fresh() ?? $subscriber;
    }

    /**
     * @param  list<int>  $excludeIds
     * @return Collection<int, NewsletterSubscriber>
     */
    public function recipientsForCampaign(array $excludeIds = []): Collection
    {
        $query = NewsletterSubscriber::query()->subscribed();

        if ($excludeIds !== []) {
            $query->whereNotIn('id', $excludeIds);
        }

        return $query->orderBy('email')->get();
    }

    /**
     * @param  list<int>  $excludeIds
     * @return array{sent: int, campaign: NewsletterCampaign|null}
     */
    public function sendCampaign(string $subject, string $body, array $excludeIds = []): array
    {
        $recipients = $this->recipientsForCampaign($excludeIds);

        if ($recipients->isEmpty()) {
            return [
                'sent' => 0,
                'campaign' => null,
            ];
        }

        $result = DB::transaction(function () use ($subject, $body, $recipients): array {
            $sent = 0;

            foreach ($recipients as $subscriber) {
                Mail::to($subscriber->email)->send(
                    new NewsletterCampaignMail($subject, $body, $subscriber)
                );
                $sent++;
            }

            $campaign = NewsletterCampaign::query()->create([
                'subject' => $subject,
                'content' => $body,
                'recipients_count' => $sent,
                'sent_at' => now(),
            ]);

            return [
                'sent' => $sent,
                'campaign' => $campaign,
            ];
        });

        if (($result['sent'] ?? 0) > 0) {
            $this->notifications->newsletterCampaignCompleted($subject, (int) $result['sent']);
        }

        return $result;
    }
}
