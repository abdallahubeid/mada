<?php

namespace App\Notifications\Tenant;

use App\Models\User;
use Illuminate\Support\Facades\Route;

class TeamMemberAccessNotification extends TenantNotification
{
    /**
     * @param  'created'|'deactivated'|'deleted'  $action
     */
    public function __construct(
        public User $member,
        public string $action,
        public string $roleName = '',
    ) {}

    protected function title(): string
    {
        return match ($this->action) {
            'created' => 'عضو فريق جديد',
            'deleted' => 'حذف عضو فريق',
            default => 'تعطيل عضو فريق',
        };
    }

    protected function message(): string
    {
        return match ($this->action) {
            'created' => "أُضيف {$this->member->name} ({$this->member->email}) بدور {$this->roleName}.",
            'deleted' => "تم حذف عضو الفريق {$this->member->name} ({$this->member->email}).",
            default => "تم تعطيل حساب {$this->member->name} ({$this->member->email}).",
        };
    }

    protected function url(): ?string
    {
        return Route::has('team.index') ? route('team.index') : null;
    }

    protected function icon(): string
    {
        return 'security';
    }

    protected function severity(): string
    {
        return $this->action === 'created' ? 'medium' : 'high';
    }

    protected function type(): string
    {
        return 'team.'.$this->action;
    }
}
