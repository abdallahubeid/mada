<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\NewsletterCampaign;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;

/**
 * Newsletter campaign history listing.
 */
class NewsletterCampaignController extends Controller
{
    public function index(): View
    {
        $campaigns = NewsletterCampaign::query()
            ->orderByDesc('sent_at')
            ->orderByDesc('id')
            ->paginate(config('app.paginate_page'));

        return view('admin.newsletter.campaigns', [
            'campaigns' => $campaigns,
        ]);
    }

    public function show(NewsletterCampaign $campaign): JsonResponse
    {
        return response()->json([
            'id' => $campaign->id,
            'subject' => $campaign->subject,
            'content' => $campaign->content,
            'recipients_count' => $campaign->recipients_count,
            'sent_at' => optional($campaign->sent_at)?->toIso8601String(),
            'sent_at_human' => $campaign->sent_at?->locale('ar')->diffForHumans(),
            'sent_at_formatted' => $campaign->sent_at?->timezone(config('app.timezone'))->format('Y-m-d H:i'),
        ]);
    }
}
