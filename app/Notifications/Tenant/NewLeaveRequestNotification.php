<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\LeaveRequest;
use Illuminate\Support\Facades\Route;

class NewLeaveRequestNotification extends TenantNotification
{
    public function __construct(public LeaveRequest $leaveRequest)
    {
        $this->leaveRequest->loadMissing(['employee', 'leaveType']);
    }

    protected function title(): string
    {
        return 'طلب إجازة جديد';
    }

    protected function message(): string
    {
        $name = $this->leaveRequest->employee?->full_name ?? 'موظف';
        $type = $this->leaveRequest->leaveType?->name ?? 'إجازة';
        $days = $this->leaveRequest->days_count;

        return "قدّم «{$name}» طلب {$type} لمدة {$days} يوم بانتظار الاعتماد.";
    }

    protected function url(): ?string
    {
        return Route::has('hr.leaves.index')
            ? route('hr.leaves.index', ['status' => 'pending'])
            : null;
    }

    protected function icon(): string
    {
        return 'leave';
    }

    protected function severity(): string
    {
        return 'high';
    }

    protected function type(): string
    {
        return 'leave.submitted';
    }
}
