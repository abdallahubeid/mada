<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\Announcement;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class UrgentAnnouncementPublished
{
    use Dispatchable, SerializesModels;

    public function __construct(public Announcement $announcement) {}
}
