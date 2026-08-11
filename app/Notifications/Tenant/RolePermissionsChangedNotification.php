<?php

namespace App\Notifications\Tenant;

use Illuminate\Support\Facades\Route;

class RolePermissionsChangedNotification extends TenantNotification
{
    /**
     * @param  'created'|'updated'|'deleted'  $action
     */
    public function __construct(
        public string $roleName,
        public string $action,
    ) {}

    protected function title(): string
    {
        return match ($this->action) {
            'created' => 'إنشاء دور',
            'deleted' => 'حذف دور',
            default => 'تحديث صلاحيات دور',
        };
    }

    protected function message(): string
    {
        return match ($this->action) {
            'created' => "تم إنشاء الدور «{$this->roleName}».",
            'deleted' => "تم حذف الدور «{$this->roleName}».",
            default => "تم تحديث صلاحيات الدور «{$this->roleName}».",
        };
    }

    protected function url(): ?string
    {
        return Route::has('roles.index') ? route('roles.index') : null;
    }

    protected function icon(): string
    {
        return 'security';
    }

    protected function severity(): string
    {
        return 'high';
    }

    protected function type(): string
    {
        return 'role.'.$this->action;
    }
}
