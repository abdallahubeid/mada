<?php

namespace App\Mail\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the tenant Owner their workspace is live again.
 *
 * Carries no reason: the suspension that prompted it has been lifted, and
 * restating it here would reopen a closed matter in the customer's inbox.
 */
class TenantReactivatedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public User $owner,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "إعادة تفعيل حساب «{$this->tenant->name}» على مدى",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenancy.tenant-reactivated',
        );
    }
}
