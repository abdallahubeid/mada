<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Enums\EvaluationPeriodType;
use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateCompanySettingRequest;
use App\Services\Tenancy\TenantAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Support\Facades\DB;

class CompanySettingController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditor $auditor,
    ) {}

    public function edit(): View
    {
        return view('tenant.settings.company', [
            'tenant' => $this->tenantContext->getTenant(),
            'settings' => OrgSetting::query()->first(),
            'calendar' => WorkCalendar::query()->first(),
            'currencies' => $this->currencies(),
            'timezones' => $this->commonTimezones(),
            'evaluationPeriodTypes' => EvaluationPeriodType::cases(),
            'weekdayLabels' => $this->weekdayLabels(),
            'canUpdate' => auth()->user()?->can('tenant.settings.update') ?? false,
        ]);
    }

    public function update(UpdateCompanySettingRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $user = $request->user();

        abort_unless($tenant !== null && $user !== null, 403);

        $data = $request->validated();

        DB::transaction(function () use ($data, $tenant, $user): void {
            $orgSetting = OrgSetting::query()->firstOrNew(['tenant_id' => $tenant->id]);
            if (! $orgSetting->exists) {
                $orgSetting->created_by = $user->id;
            }
            $orgSetting->fill([
                'currency' => strtoupper($data['currency']),
                'timezone' => $data['timezone'],
                'evaluation_periodicity' => $data['evaluation_periodicity'],
                'updated_by' => $user->id,
            ]);
            $orgSetting->save();

            $workingDays = collect($data['working_days'])
                ->map(fn ($day): int => (int) $day)
                ->unique()
                ->sort()
                ->values()
                ->all();

            $holidays = collect($data['holidays'] ?? [])
                ->filter(fn (array $row): bool => filled($row['date'] ?? null) && filled($row['name'] ?? null))
                ->map(fn (array $row): array => [
                    'date' => $row['date'],
                    'name' => $row['name'],
                ])
                ->values()
                ->all();

            $calendar = WorkCalendar::query()->firstOrNew([
                'tenant_id' => $tenant->id,
                'name' => 'Default',
            ]);
            if (! $calendar->exists) {
                $calendar->created_by = $user->id;
            }
            $calendar->fill([
                'working_days' => $workingDays,
                'holidays' => $holidays,
                'updated_by' => $user->id,
            ]);
            $calendar->save();
        });

        $this->auditor->log('settings.updated', 'settings', $tenant, [
            'currency' => strtoupper($data['currency']),
            'timezone' => $data['timezone'],
        ]);

        flash()->info('تم تحديث إعدادات المؤسسة بنجاح.');

        return redirect()->route('settings.company');
    }

    /**
     * @return array<string, string>
     */
    private function currencies(): array
    {
        return [
            'SAR' => 'ريال سعودي (SAR)',
            'AED' => 'درهم إماراتي (AED)',
            'EGP' => 'جنيه مصري (EGP)',
            'USD' => 'دولار أمريكي (USD)',
            'EUR' => 'يورو (EUR)',
        ];
    }

    /**
     * @return array<string, string>
     */
    private function commonTimezones(): array
    {
        return [
            'Asia/Riyadh' => 'الرياض (Asia/Riyadh)',
            'Asia/Dubai' => 'دبي (Asia/Dubai)',
            'Africa/Cairo' => 'القاهرة (Africa/Cairo)',
            'Asia/Amman' => 'عمّان (Asia/Amman)',
            'Asia/Kuwait' => 'الكويت (Asia/Kuwait)',
            'Asia/Bahrain' => 'البحرين (Asia/Bahrain)',
            'Asia/Qatar' => 'قطر (Asia/Qatar)',
            'UTC' => 'UTC',
        ];
    }

    /**
     * @return array<int, string>
     */
    private function weekdayLabels(): array
    {
        return [
            0 => 'الأحد',
            1 => 'الإثنين',
            2 => 'الثلاثاء',
            3 => 'الأربعاء',
            4 => 'الخميس',
            5 => 'الجمعة',
            6 => 'السبت',
        ];
    }
}
