<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\LeaveRequest;
use Illuminate\Support\Facades\Route;

class LeaveDecisionNotification extends TenantNotification
{
    /**
     * @param  'approved'|'rejected'  $decision
     */
    public function __construct(
        public LeaveRequest $leaveRequest,
        public string $decision,
    ) {
        $this->leaveRequest->loadMissing('leaveType');
    }

    protected function title(): string
    {
        return $this->decision === 'approved'
            ? 'تم اعتماد طلب إجازتك'
            : 'تم رفض طلب إجازتك';
    }

    protected function message(): string
    {
        $type = $this->leaveRequest->leaveType?->name ?? 'إجازة';
        $days = $this->leaveRequest->days_count;
        $start = $this->leaveRequest->start_date?->format('Y-m-d');

        if ($this->decision === 'approved') {
            return "تم اعتماد طلب {$type} لمدة {$days} يوم ابتداءً من {$start}.";
        }

        $reason = $this->leaveRequest->rejection_reason;

        return filled($reason)
            ? "تم رفض طلب {$type} لمدة {$days} يوم. السبب: {$reason}"
            : "تم رفض طلب {$type} لمدة {$days} يوم.";
    }

    protected function url(): ?string
    {
        return Route::has('tenant.hr.my-leaves')
            ? route('tenant.hr.my-leaves')
            : null;
    }

    protected function icon(): string
    {
        return 'leave';
    }

    protected function severity(): string
    {
        return $this->decision === 'approved' ? 'medium' : 'high';
    }

    protected function type(): string
    {
        return "leave.{$this->decision}";
    }
}
