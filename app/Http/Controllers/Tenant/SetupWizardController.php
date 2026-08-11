<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\OrgSetting;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\CompleteSetupWizardRequest;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

/**
 * Multi-step onboarding wizard for verified Owners whose tenant is not yet active (BR-202).
 */
class SetupWizardController extends Controller
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    public function show(): View|RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();

        abort_unless($tenant !== null, 403);
        abort_unless(auth()->user()?->can('tenant.settings.update'), 403);

        if ($tenant->isActive()) {
            return redirect()->route('dashboard');
        }

        $settings = OrgSetting::query()->first();
        $calendar = WorkCalendar::query()->first();

        return view('tenant.setup.index', [
            'tenant' => $tenant,
            'settings' => $settings,
            'calendar' => $calendar,
            'currencies' => $this->currencies(),
            'timezones' => $this->commonTimezones(),
            'weekdayLabels' => $this->weekdayLabels(),
        ]);
    }

    public function update(CompleteSetupWizardRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $user = $request->user();

        abort_unless($tenant !== null && $user !== null, 403);

        if ($tenant->isActive()) {
            return redirect()->route('dashboard');
        }

        $data = $request->validated();

        DB::transaction(function () use ($data, $request, $tenant, $user): void {
            $user->forceFill([
                'password' => Hash::make($data['password']),
            ])->save();

            if ($request->hasFile('logo')) {
                $this->storeLogo($request->file('logo'), $tenant->id, $user->id);
            }

            $orgSetting = OrgSetting::query()->firstOrNew(['tenant_id' => $tenant->id]);
            if (! $orgSetting->exists) {
                $orgSetting->created_by = $user->id;
            }
            $orgSetting->fill([
                'currency' => strtoupper($data['currency']),
                'timezone' => $data['timezone'],
                'setup_completed_at' => now(),
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

        flash()->success('تم حفظ إعدادات المؤسسة بنجاح. حسابك ما زال قيد مراجعة فريق Veyra.');

        return redirect()->route('dashboard.setup');
    }

    private function storeLogo(UploadedFile $file, int $tenantId, int $userId): void
    {
        $tenant = $this->tenantContext->getTenant();
        abort_unless($tenant !== null && $tenant->id === $tenantId, 403);

        $tenant->images()->where('collection', 'logo')->get()->each->forceDelete();

        $path = $file->store('tenant/logo', 'custom');

        $tenant->images()->create([
            'collection' => 'logo',
            'disk' => 'custom',
            'path' => $path,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType(),
            'file_size' => $file->getSize(),
            'alt_text' => $tenant->name,
            'sort_order' => 0,
        ]);
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
     * ISO-8601 weekday numbers: 0 = Sunday … 6 = Saturday.
     *
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
