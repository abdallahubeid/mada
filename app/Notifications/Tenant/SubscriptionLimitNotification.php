<?php

namespace App\Notifications\Tenant;

use Illuminate\Support\Facades\Route;

class SubscriptionLimitNotification extends TenantNotification
{
    /**
     * @param  'approaching'|'reached'|'renewal'  $action
     */
    public function __construct(
        public string $action,
        public string $label = '',
        public int $used = 0,
        public ?int $limit = null,
        public float $percent = 0,
        public ?int $daysRemaining = null,
    ) {}

    protected function title(): string
    {
        return match ($this->action) {
            'reached' => 'تجاوز حد الخطة',
            'renewal' => 'تجديد الاشتراك',
            default => 'اقتراب حد الخطة',
        };
    }

    protected function message(): string
    {
        return match ($this->action) {
            'reached' => "وصلت إلى الحد الأقصى لـ {$this->label}: {$this->used}/{$this->limit}. تعذّر إنشاء المزيد.",
            'renewal' => "ينتهي الاشتراك خلال {$this->daysRemaining} يوم. يُرجى التجديد لتجنب انقطاع الخدمة.",
            default => "{$this->label}: {$this->used}/{$this->limit} ({$this->percent}%). اقتربت من حد خطتك.",
        };
    }

    protected function url(): ?string
    {
        return Route::has('tenant.subscription.index')
            ? route('tenant.subscription.index')
            : null;
    }

    protected function icon(): string
    {
        return 'billing';
    }

    protected function severity(): string
    {
        return match ($this->action) {
            'approaching' => 'high',
            default => 'critical',
        };
    }

    protected function type(): string
    {
        return 'subscription.'.$this->action;
    }
}
