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
 * Tells the tenant Owner their workspace has been suspended, and why.
 *
 * The reason is passed explicitly rather than read off the tenant inside the
 * view: reactivation clears `suspension_reason`, so a view that read the model
 * would render an empty panel for any message still queued when the suspension
 * was lifted. Same reasoning as TenantRejectedMail.
 */
class TenantSuspendedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public User $owner,
        public string $reason,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "إيقاف مؤقت لحساب «{$this->tenant->name}» على Veyra",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenancy.tenant-suspended',
        );
    }
}
