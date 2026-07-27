<?php

namespace App\Http\Controllers\Marketing;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketing\ContactRequest;
use App\Mail\Marketing\ContactInquiry;
use App\Services\Support\SupportInbox;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Mail;

/**
 * Contact / Book-a-Demo page (docs/MARKETING.md §2 / §2.1).
 * Persists into support threads (grouped by email) and emails the platform inbox.
 */
class ContactController extends Controller
{
    public function __construct(private SupportInbox $inbox) {}

    public function create(): View
    {
        return view('marketing.contact', [
            'subjects' => ContactRequest::SUBJECTS,
        ]);
    }

    public function store(ContactRequest $request): RedirectResponse
    {
        $inquiry = $request->validated();

        $this->inbox->ingestContactInquiry($inquiry, Auth::user());

        Mail::to(config('mail.from.address'))
            ->send(new ContactInquiry($inquiry));

        flash()->success('شكرًا لتواصلك! تم إرسال رسالتك بنجاح وسنتواصل معك قريبًا.');

        return redirect()->route('marketing.contact');
    }
}
