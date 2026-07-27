<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\NewsletterRequest;
use App\Services\Newsletter\NewsletterService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

/**
 * Public newsletter subscribe / unsubscribe (footer + email links).
 */
class NewsletterController extends Controller
{
    public function __construct(private NewsletterService $newsletter) {}

    public function subscribe(NewsletterRequest $request): RedirectResponse
    {
        $result = $this->newsletter->subscribe($request->validated('email'));

        if ($result['already_subscribed']) {
            flash()->info('أنت مشترك بالفعل في النشرة البريدية');
        } else {
            flash()->success('تم اشتراكك بنجاح في النشرة البريدية');
        }

        return back();
    }

    public function unsubscribe(string $email): View
    {
        $subscriber = $this->newsletter->unsubscribeByEmail(urldecode($email));

        return view('marketing.newsletter-unsubscribe', [
            'found' => $subscriber !== null,
            'email' => $subscriber?->email ?? urldecode($email),
        ]);
    }
}
