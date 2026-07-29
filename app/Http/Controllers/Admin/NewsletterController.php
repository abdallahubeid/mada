<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\SendNewsletterCampaignRequest;
use App\Models\NewsletterSubscriber;
use App\Services\Admin\TrashManager;
use App\Services\Newsletter\NewsletterDashboardPoller;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Admin newsletter subscribers dashboard + campaign composer.
 */
class NewsletterController extends Controller
{
    public function __construct(
        private NewsletterService $newsletter,
        private NewsletterDashboardPoller $poller,
    ) {}

    public function index(Request $request): View
    {
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        $snapshot = $this->poller->snapshot($status, $search, $page);

        $query = NewsletterSubscriber::query()
            ->orderByDesc('subscribed_at')
            ->orderByDesc('id');

        if ($status === NewsletterSubscriber::STATUS_SUBSCRIBED) {
            $query->subscribed();
        } elseif ($status === NewsletterSubscriber::STATUS_UNSUBSCRIBED) {
            $query->unsubscribed();
        }

        if ($search !== '') {
            $query->where('email', 'like', "%{$search}%");
        }

        $subscribers = $query->paginate(config('app.paginate_page'))->withQueryString();

        return view('admin.newsletter.index', [
            'subscribers' => $subscribers,
            'subscriberRows' => $snapshot['subscribers'],
            'activeSubscribers' => $snapshot['active_subscribers'],
            'status' => $status,
            'search' => $search,
            'stats' => $snapshot['stats'],
            'pollSignature' => $snapshot['signature'],
        ]);
    }

    public function poll(Request $request): JsonResponse
    {
        $status = (string) $request->query('status', 'all');
        $search = trim((string) $request->query('q', ''));
        $page = max(1, (int) $request->query('page', 1));

        return response()->json(
            $this->poller->snapshot($status, $search, $page)
        );
    }

    public function toggle(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->toggleStatus();

        flash()->success(
            $subscriber->isSubscribed()
                ? 'تم تفعيل اشتراك المشترك.'
                : 'تم إلغاء اشتراك المشترك.'
        );

        return back();
    }

    public function destroy(NewsletterSubscriber $subscriber): RedirectResponse
    {
        $subscriber->delete();

        app(TrashManager::class)->flashSoftDeleted('تم حذف المشترك بنجاح.', 'newsletter-subscribers', $subscriber);

        return back();
    }

    public function export(): StreamedResponse
    {
        $filename = 'newsletter-subscribers-'.now()->format('Y-m-d').'.csv';

        return response()->streamDownload(function (): void {
            $handle = fopen('php://output', 'w');

            if ($handle === false) {
                return;
            }

            fwrite($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['email', 'status', 'subscribed_at']);

            NewsletterSubscriber::query()
                ->subscribed()
                ->orderBy('email')
                ->cursor()
                ->each(function (NewsletterSubscriber $subscriber) use ($handle): void {
                    fputcsv($handle, [
                        $subscriber->email,
                        $subscriber->status,
                        optional($subscriber->subscribed_at)?->toDateTimeString(),
                    ]);
                });

            fclose($handle);
        }, $filename, [
            'Content-Type' => 'text/csv; charset=UTF-8',
        ]);
    }

    public function sendCampaign(SendNewsletterCampaignRequest $request): RedirectResponse
    {
        $validated = $request->validated();
        $excludeIds = array_map('intval', $validated['exclude_ids'] ?? []);

        $result = $this->newsletter->sendCampaign(
            $validated['subject'],
            $validated['body'],
            $excludeIds,
        );

        $sent = $result['sent'];

        if ($sent === 0) {
            flash()->warning('لا يوجد مشتركون نشطون لإرسال الحملة.');
        } else {
            flash()->success("تم إرسال الحملة إلى {$sent} مشتركًا.");
        }

        return back();
    }
}
