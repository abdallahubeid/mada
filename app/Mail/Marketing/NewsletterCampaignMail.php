<?php

namespace App\Mail\Marketing;

use App\Models\NewsletterSubscriber;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Attachment;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Admin-composed newsletter campaign message.
 */
class NewsletterCampaignMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public string $campaignSubject,
        public string $bodyHtml,
        public NewsletterSubscriber $subscriber,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: $this->campaignSubject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.marketing.newsletter-campaign',
            with: [
                'bodyHtml' => $this->bodyHtml,
                'unsubscribeUrl' => route('marketing.newsletter.unsubscribe', [
                    'email' => $this->subscriber->email,
                ]),
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
