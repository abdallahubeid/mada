<?php

namespace App\Events\Tenancy;

use App\Domain\Tenancy\Models\Attendance;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

class AttendanceMarkedLate
{
    use Dispatchable, SerializesModels;

    public function __construct(public Attendance $attendance) {}
}
