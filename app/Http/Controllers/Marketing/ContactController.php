<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\ContactRequest;
use App\Mail\Marketing\ContactInquiry;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Mail;

/**
 * Contact / Book-a-Demo page (docs/MARKETING.md §2 / §2.1).
 * GET renders the form; POST validates, throttles, and emails the platform inbox via SMTP (Maildev in local).
 */
class ContactController extends Controller
{
    public function create(): View
    {
        return view('marketing.contact', [
            'subjects' => ContactRequest::SUBJECTS,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $inquiry = $request->validated();

        Mail::to(config('mail.from.address'))
            ->send(new ContactInquiry($inquiry));

        return redirect()
            ->route('marketing.contact')
            ->with('status', 'تم إرسال رسالتك بنجاح. سنتواصل معك قريباً.');
    }
}
