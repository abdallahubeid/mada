<?php

namespace App\Mail\Marketing;

use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Internal notification of a footer newsletter signup.
 * Sent synchronously for immediate Maildev visibility in local development.
 */
class NewsletterSubscribed extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(public string $email) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "اشتراك جديد في النشرة — {$this->email}",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.marketing.newsletter-subscribed',
            with: [
                'email' => $this->email,
            ],
        );
    }

    /**
     * @return array<int, Attachment>
     */
    public function attachments(): array
    {
        return [];
    }
}
