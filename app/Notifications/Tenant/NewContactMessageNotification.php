<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\TenantContactMessage;
use App\Domain\Tenancy\Models\TenantContactThread;
use Illuminate\Support\Facades\Route;

class NewContactMessageNotification extends TenantNotification
{
    public function __construct(
        public TenantContactThread $thread,
        public TenantContactMessage $message,
    ) {}

    protected function title(): string
    {
        return 'رسالة تواصل جديدة';
    }

    protected function message(): string
    {
        $name = $this->thread->sender_name;
        $subject = $this->thread->subject;

        return "وصلت رسالة من «{$name}» بخصوص: {$subject}";
    }

    protected function url(): ?string
    {
        return Route::has('tenant.contact-messages.index')
            ? route('tenant.contact-messages.index')
            : null;
    }

    protected function icon(): string
    {
        return 'message';
    }

    protected function severity(): string
    {
        return 'high';
    }

    protected function type(): string
    {
        return 'contact.message';
    }
}
