<?php

namespace App\Mail\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\TenantInvitation;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class TenantInvitationMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public TenantInvitation $invitation,
        public Tenant $tenant,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'دعوة للانضمام إلى '.$this->tenant->name.' على Veyra',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenancy.invitation',
            with: [
                'tenantName' => $this->tenant->name,
                'role' => $this->invitation->role,
                'email' => $this->invitation->email,
                'expiresAt' => $this->invitation->expires_at,
                'token' => $this->invitation->token,
            ],
        );
    }
}
