<?php

namespace App\Mail\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\Plan;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

/**
 * Tells the tenant Owner their registration was approved and the workspace is live.
 */
class TenantApprovedMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public Tenant $tenant,
        public User $owner,
        public ?Plan $plan = null,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: "تم تفعيل حساب «{$this->tenant->name}» على Veyra",
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenancy.tenant-approved',
        );
    }
}
