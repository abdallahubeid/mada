<?php

namespace App\Mail\Tenancy;

use App\Domain\Tenancy\Models\Tenant;
use App\Models\User;
use Illuminate\Bus\Queueable;
use Illuminate\Mail\Mailable;
use Illuminate\Mail\Mailables\Content;
use Illuminate\Mail\Mailables\Envelope;
use Illuminate\Queue\SerializesModels;

class EmployeeWelcomeMail extends Mailable
{
    use Queueable, SerializesModels;

    public function __construct(
        public User $user,
        public Tenant $tenant,
        public string $plainPassword,
        public string $roleLabel,
    ) {}

    public function envelope(): Envelope
    {
        return new Envelope(
            subject: 'بيانات الدخول إلى '.$this->tenant->name.' على Veyra',
        );
    }

    public function content(): Content
    {
        return new Content(
            markdown: 'emails.tenancy.employee-welcome',
            with: [
                'tenantName' => $this->tenant->name,
                'userName' => $this->user->name,
                'email' => $this->user->email,
                'plainPassword' => $this->plainPassword,
                'roleLabel' => $this->roleLabel,
                'loginUrl' => route('login'),
            ],
        );
    }
}
