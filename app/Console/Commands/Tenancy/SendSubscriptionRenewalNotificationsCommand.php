<?php

namespace App\Console\Commands\Tenancy;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\SubscriptionRenewalDue;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendSubscriptionRenewalNotificationsCommand extends Command
{
    protected $signature = 'tenant:send-subscription-renewal-notifications {--days=7 : Days ahead to warn}';

    protected $description = 'Notify Tenant Owners when subscription renews within N days';

    public function handle(TenantContext $tenantContext): int
    {
        $days = max(1, (int) $this->option('days'));
        $sent = 0;

        Tenant::query()
            ->where('status', TenantStatus::Active)
            ->whereNotNull('renews_at')
            ->whereDate('renews_at', '>=', now()->toDateString())
            ->whereDate('renews_at', '<=', now()->addDays($days)->toDateString())
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($tenantContext, &$sent): void {
                $tenantContext->setTenant($tenant);

                $cacheKey = sprintf(
                    'tenant:%d:subscription-renewal:%s',
                    $tenant->id,
                    now()->toDateString(),
                );

                if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                    return;
                }

                $daysRemaining = (int) now()->startOfDay()->diffInDays(
                    $tenant->renews_at->copy()->startOfDay(),
                    false,
                );

                event(new SubscriptionRenewalDue(
                    $tenant,
                    $tenant->renews_at,
                    max(0, $daysRemaining),
                ));

                $sent++;
            });

        $tenantContext->setTenant(null);

        $this->info("Sent {$sent} subscription-renewal notification(s).");

        return self::SUCCESS;
    }
}
