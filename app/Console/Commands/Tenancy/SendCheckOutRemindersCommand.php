<?php

namespace App\Console\Commands\Tenancy;

use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Notifications\Tenant\CheckOutReminderNotification;
use App\Services\Tenancy\TenantNotifier;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Cache;

/**
 * Reminds employees who checked in today but never checked out.
 *
 * Scheduled late in the working day (see routes/console.php). Idempotent per
 * employee per day via a cache key that expires at midnight, matching
 * {@see SendExpiringContractNotificationsCommand}.
 */
class SendCheckOutRemindersCommand extends Command
{
    protected $signature = 'tenant:send-check-out-reminders';

    protected $description = 'Remind employees who checked in today but have not checked out';

    public function handle(TenantContext $tenantContext, TenantNotifier $notifier): int
    {
        $sent = 0;

        Tenant::query()
            ->where('status', TenantStatus::Active)
            ->orderBy('id')
            ->each(function (Tenant $tenant) use ($tenantContext, $notifier, &$sent): void {
                $tenantContext->setTenant($tenant);

                Attendance::query()
                    ->with('employee')
                    ->whereDate('date', now()->toDateString())
                    ->whereNotNull('check_in')
                    ->whereNull('check_out')
                    ->each(function (Attendance $attendance) use ($tenant, $notifier, &$sent): void {
                        if ($attendance->employee?->user_id === null) {
                            return;
                        }

                        $cacheKey = sprintf(
                            'tenant:%d:checkout-reminder:%d:%s',
                            $tenant->id,
                            $attendance->id,
                            now()->toDateString(),
                        );

                        if (! Cache::add($cacheKey, true, now()->endOfDay())) {
                            return;
                        }

                        $notifier->toEmployee(
                            $attendance->employee,
                            new CheckOutReminderNotification($attendance),
                        );

                        $sent++;
                    });
            });

        $tenantContext->setTenant(null);

        $this->info("Sent {$sent} check-out reminder(s).");

        return self::SUCCESS;
    }
}
