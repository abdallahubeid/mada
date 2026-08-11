<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantContext;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\UpdateWorkScheduleRequest;
use App\Services\Tenancy\TenantAuditor;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class WorkScheduleController extends Controller
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly TenantAuditor $auditor,
    ) {}

    public function edit(): View
    {
        $calendar = WorkCalendar::query()->first();

        return view('tenant.settings.work-schedule', [
            'calendar' => $calendar,
            'weekdayLabels' => $this->weekdayLabels(),
            'canUpdate' => auth()->user()?->can('tenant.settings.update') ?? false,
        ]);
    }

    public function update(UpdateWorkScheduleRequest $request): RedirectResponse
    {
        $tenant = $this->tenantContext->getTenant();
        $user = $request->user();

        abort_unless($tenant !== null && $user !== null, 403);

        $data = $request->validated();
        $weekendDays = collect($data['weekend_days'] ?? [])
            ->map(fn ($day): int => (int) $day)
            ->unique()
            ->sort()
            ->values()
            ->all();

        $workingDays = array_values(array_diff(range(0, 6), $weekendDays));

        $calendar = WorkCalendar::query()->firstOrNew([
            'tenant_id' => $tenant->id,
            'name' => 'Default',
        ]);

        if (! $calendar->exists) {
            $calendar->created_by = $user->id;
            $calendar->holidays = $calendar->holidays ?? [];
        }

        $calendar->fill([
            'work_start_time' => $data['work_start_time'],
            'work_end_time' => $data['work_end_time'],
            'grace_period_minutes' => (int) $data['grace_period_minutes'],
            'weekend_days' => $weekendDays,
            'working_days' => $workingDays === [] ? range(0, 6) : $workingDays,
            'updated_by' => $user->id,
        ]);
        $calendar->save();

        $this->auditor->log('settings.work_schedule_updated', 'settings', $calendar, [
            'work_start_time' => $data['work_start_time'],
            'work_end_time' => $data['work_end_time'],
            'grace_period_minutes' => (int) $data['grace_period_minutes'],
            'weekend_days' => $weekendDays,
        ]);

        flash()->info('تم تحديث جدول العمل بنجاح.');

        return redirect()->route('settings.work-schedule');
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
