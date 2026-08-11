<?php

namespace App\Console\Commands\Tenancy;

use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Events\Tenancy\ContractExpiringSoon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

class SendExpiringContractNotificationsCommand extends Command
{
    protected $signature = 'tenant:send-expiring-contract-notifications {--days=30 : Days ahead to warn}';

    protected $description = 'Notify Tenant Owners about contracts expiring within N days';

    public function handle(TenantContext $tenantContext): int
    {
        $days = max(1, (int) $this->option('days'));
        $sent = 0;

        Tenant::query()
            ->where('status', TenantStatus::Active)
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($tenantContext, $days, &$sent): void {
                $tenantContext->setTenant($tenant);

                EmployeeContract::query()
                    ->with('employee')
                    ->where('status', ContractStatus::Active)
                    ->whereNotNull('end_date')
                    ->whereDate('end_date', '>=', now()->toDateString())
                    ->whereDate('end_date', '<=', now()->addDays($days)->toDateString())
                    ->each(function (EmployeeContract $contract) use ($tenant, &$sent): void {
                        $cacheKey = sprintf(
                            'tenant:%d:contract-expiring:%d:%s',
                            $tenant->id,
                            $contract->id,
                            now()->toDateString(),
                        );

                        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                            return;
                        }

                        event(new ContractExpiringSoon($contract));
                        $sent++;
                    });
            });

        $tenantContext->setTenant(null);

        $this->info("Sent {$sent} expiring-contract notification(s).");

        return self::SUCCESS;
    }
}
