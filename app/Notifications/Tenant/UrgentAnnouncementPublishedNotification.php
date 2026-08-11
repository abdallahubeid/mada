<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Announcement;
use Illuminate\Support\Facades\Route;

class UrgentAnnouncementPublishedNotification extends TenantNotification
{
    public function __construct(public Announcement $announcement) {}

    protected function title(): string
    {
        return 'تعميم عاجل';
    }

    protected function message(): string
    {
        return $this->announcement->title;
    }

    protected function url(): ?string
    {
        return Route::has('tenant.announcements.index')
            ? route('tenant.announcements.index')
            : null;
    }

    protected function icon(): string
    {
        return 'announcement';
    }

    protected function severity(): string
    {
        return 'high';
    }

    protected function type(): string
    {
        return 'announcement.urgent';
    }
}
