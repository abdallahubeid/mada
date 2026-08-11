<?php

namespace App\Notifications\Tenant;

use App\Domain\Tenancy\Models\EmployeeContract;
use Illuminate\Support\Facades\Route;

class ContractLifecycleNotification extends TenantNotification
{
    /**
     * @param  'created'|'updated'|'terminated'|'expiring'  $action
     */
    public function __construct(
        public EmployeeContract $contract,
        public string $action,
    ) {
        $this->contract->loadMissing(['employee']);
    }

    protected function title(): string
    {
        return match ($this->action) {
            'created' => 'عقد جديد',
            'terminated' => 'إنهاء عقد',
            'expiring' => 'عقد على وشك الانتهاء',
            default => 'تحديث عقد',
        };
    }

    protected function message(): string
    {
        $name = $this->contract->employee?->full_name ?? 'موظف';
        $end = optional($this->contract->end_date)?->format('Y-m-d') ?? '—';

        return match ($this->action) {
            'created' => "تم إنشاء عقد لـ «{$name}».",
            'terminated' => "أُنهي / انتهى عقد «{$name}» (الحالة: {$this->contract->status->label()}).",
            'expiring' => "عقد «{$name}» ينتهي في {$end}.",
            default => "تم تحديث عقد «{$name}».",
        };
    }

    protected function url(): ?string
    {
        if ($this->action === 'expiring' && Route::has('hr.contracts.index')) {
            return route('hr.contracts.index', ['expiring' => 1]);
        }

        return Route::has('hr.contracts.index') ? route('hr.contracts.index') : null;
    }

    protected function icon(): string
    {
        return 'contract';
    }

    protected function severity(): string
    {
        return match ($this->action) {
            'terminated', 'expiring' => 'high',
            default => 'medium',
        };
    }

    protected function type(): string
    {
        return 'contract.'.$this->action;
    }
}
