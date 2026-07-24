<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\NewsletterRequest;
use App\Mail\Marketing\NewsletterSubscribed;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

/**
 * Footer newsletter signup (docs/MARKETING.md §2.1).
 * Validates + throttles, then notifies the platform inbox via SMTP.
 */
class NewsletterController extends Controller
{
    public function __invoke(NewsletterRequest $request): RedirectResponse
    {
        $email = $request->validated('email');

        Mail::to(config('mail.from.address'))
            ->send(new NewsletterSubscribed($email));

        return back()->with('newsletter_status', 'تم الاشتراك بنجاح. شكراً لاهتمامك!');
    }
}
