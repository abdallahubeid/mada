<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Attendance;
use Illuminate\Support\Facades\Route;

class AttendanceMarkedLateNotification extends TenantNotification
{
    public function __construct(public Attendance $attendance)
    {
        $this->attendance->loadMissing(['employee']);
    }

    protected function title(): string
    {
        return 'تأخير حضور';
    }

    protected function message(): string
    {
        $name = $this->attendance->employee?->full_name ?? 'موظف';
        $time = optional($this->attendance->check_in)?->format('H:i') ?? '—';

        return "سجّل «{$name}» حضوراً متأخراً اليوم الساعة {$time}.";
    }

    protected function url(): ?string
    {
        return Route::has('hr.attendance.index') ? route('hr.attendance.index') : null;
    }

    protected function icon(): string
    {
        return 'attendance';
    }

    protected function severity(): string
    {
        return 'medium';
    }

    protected function type(): string
    {
        return 'attendance.late';
    }
}
