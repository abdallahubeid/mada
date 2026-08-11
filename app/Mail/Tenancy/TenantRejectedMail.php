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
 * Tells the tenant Owner their registration was declined, and why.
 *
 * The reason is passed explicitly rather than read off the tenant inside the
 * view, so the message a customer received is fixed at send time even if a
 * Super Admin later edits the stored reason.
 */
class TenantRejectedMail extends Mailable
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
            subject: "بخصوص طلب تسجيل «{$this->tenant->name}» على Veyra",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenancy.tenant-rejected',
        );
    }
}
