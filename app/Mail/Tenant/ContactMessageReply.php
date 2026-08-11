<?php

namespace App\Mail\Tenant;

use App\Domain\Tenancy\Models\TenantContactMessage;
use App\Domain\Tenancy\Models\TenantContactThread;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class ContactMessageReply extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantContactThread $thread,
        public TenantContactMessage $message,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'رد على: '.$this->thread->subject,
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenant.contact-message-reply',
        );
    }
}
