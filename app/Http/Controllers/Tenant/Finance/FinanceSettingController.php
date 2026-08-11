<?php

namespace App\Http\Controllers\Tenant\Finance;

use App\Domain\Finance\Actions\GenerateSettlementAction;
use App\Domain\Finance\Models\FinanceSetting;
use App\Domain\Finance\Models\OffboardingSettlement;
use App\Domain\Finance\Support\EosbPolicy;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\Finance\UpdateFinanceSettingRequest;
use App\Services\Tenancy\TenantAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

/**
 * The tenant's finance configuration screen — currently the EOSB rules.
 *
 * Gated by `finance.settings.manage`, held by the Owner and the Finance
 * Manager only. Unlike the rest of the module there is no maker-checker split
 * here: this screen sets the rules, it does not authorize a payment, and every
 * settlement produced under a set of rules snapshots them (see
 * {@see GenerateSettlementAction}), so an edit can
 * never restate what an approved settlement already paid.
 */
class FinanceSettingController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditor $auditor,
    ) {}

    public function edit(): View
    {
        $settings = FinanceSetting::current();

        return view('tenant.finance.settings.edit', [
            'settings' => $settings,
            'policy' => $settings->eosbPolicy(),
            'isConfigured' => $settings->exists,

            /*
             * Settlements already generated under whatever rules were in force
             * at the time. Shown so whoever edits understands the change is
             * forward-only — it will not restate any of these.
             */
            'settledCount' => OffboardingSettlement::query()->count(),
        ]);
    }

    public function update(UpdateFinanceSettingRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $user = $request->user();

        abort_unless($tenant !== null && $user !== null, 403);

        $attributes = $request->settingAttributes();

        DB::transaction(function () use ($attributes, $tenant, $user): void {
            $settings = FinanceSetting::query()->firstOrNew(['tenant_id' => $tenant->id]);

            if (! $settings->exists) {
                $settings->created_by = $user->id;
            }

            $settings->fill([...$attributes, 'updated_by' => $user->id]);
            $settings->save();
        });

        /*
         * Audited in basis points, matching what is stored — a reviewer
         * reconciling an old settlement's snapshot against this log should not
         * have to convert units to compare them.
         */
        $this->auditor->log('finance_settings.updated', 'finance', $tenant, [
            'eosb_enabled' => $attributes['eosb_enabled'],
            'eosb_tier_boundary_months' => $attributes['eosb_tier_boundary_months'],
            'eosb_lower_tier_bps' => $attributes['eosb_lower_tier_bps'],
            'eosb_upper_tier_bps' => $attributes['eosb_upper_tier_bps'],
            'eosb_resignation_taper' => $attributes['eosb_resignation_taper'],
            'nominal_month_days' => $attributes['nominal_month_days'],
            'nominal_day_hours' => $attributes['nominal_day_hours'],
        ]);

        flash()->info('تم تحديث إعدادات نهاية الخدمة. تُطبَّق على التسويات الجديدة فقط.');

        return redirect()->route('finance.settings.edit');
    }

    /**
     * Restore the shipped GCC/Saudi defaults.
     *
     * Kept as its own transition rather than a "reset" checkbox on the form so
     * that discarding a tenant's configured rules is an explicit, confirmable
     * act with its own audit entry.
     */
    public function reset(): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $user = request()->user();

        abort_unless($tenant !== null && $user !== null, 403);

        $policy = EosbPolicy::default();

        $settings = FinanceSetting::query()->firstOrNew(['tenant_id' => $tenant->id]);

        if (! $settings->exists) {
            $settings->created_by = $user->id;
        }

        $settings->fill([
            'eosb_enabled' => $policy->enabled,
            'eosb_tier_boundary_months' => $policy->tierBoundaryMonths,
            'eosb_lower_tier_bps' => $policy->lowerTierBps,
            'eosb_upper_tier_bps' => $policy->upperTierBps,
            'eosb_resignation_taper' => $policy->resignationTaper,
            'nominal_month_days' => $policy->nominalMonthDays,
            'nominal_day_hours' => $policy->nominalDayHours,
            'updated_by' => $user->id,
        ]);
        $settings->save();

        $this->auditor->log('finance_settings.reset', 'finance', $tenant, $policy->toArray());

        flash()->warning('تمت استعادة القيم الافتراضية لقواعد نهاية الخدمة.');

        return redirect()->route('finance.settings.edit');
    }
}
