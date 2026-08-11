<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\Attendance;
use Illuminate\Support\Facades\Route;

class CheckOutReminderNotification extends TenantNotification
{
    public function __construct(public Attendance $attendance) {}

    protected function title(): string
    {
        return 'تذكير بتسجيل الانصراف';
    }

    protected function message(): string
    {
        $checkIn = $this->attendance->check_in?->format('H:i');

        return $checkIn === null
            ? 'لم تسجّل انصرافك اليوم بعد.'
            : "سجّلت حضورك اليوم الساعة {$checkIn} ولم تسجّل انصرافك بعد.";
    }

    protected function url(): ?string
    {
        return Route::has('tenant.hr.my-attendance')
            ? route('tenant.hr.my-attendance')
            : null;
    }

    protected function icon(): string
    {
        return 'clock';
    }

    protected function severity(): string
    {
        return 'medium';
    }

    protected function type(): string
    {
        return 'attendance.checkout_reminder';
    }
}
