<?php

namespace App\Mail\Marketing;

use App\Http\Requests\Marketing\ContactRequest;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Address;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notification of a public contact-form submission.
 * Sent synchronously so Maildev (local SMTP) receives it without a queue worker.
 *
 * @phpstan-type ContactPayload array{name: string, email: string, company: ?string, subject: string, message: string}
 */
class ContactInquiry extends Mailable
{
    use Queueable, SerializesModels;

    /**
     * @param  ContactPayload  $inquiry
     */
    public function __construct(public array $inquiry) {}

    public function envelope(): Envelope
    {
        $subjectLabel = ContactRequest::SUBJECTS[$this->inquiry['subject']] ?? $this->inquiry['subject'];

        return new Envelope(
            replyTo: [
                new Address($this->inquiry['email'], $this->inquiry['name']),
            ],
            subject: "استفسار جديد: {$subjectLabel} — {$this->inquiry['name']}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.marketing.contact-inquiry',
            with: [
                'inquiry' => $this->inquiry,
                'subjectLabel' => ContactRequest::SUBJECTS[$this->inquiry['subject']] ?? $this->inquiry['subject'],
            ],
        );
    }

    /**
     * @return array<int, \Illuminate\Mail\Mailables\Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
