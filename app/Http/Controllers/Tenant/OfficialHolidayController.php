<?php

namespace App\Http\Controllers\Tenant;

use App\Domain\Tenancy\Models\OfficialHoliday;
use App\Http\Controllers\Controller;
use App\Http\Requests\Tenant\StoreOfficialHolidayRequest;
use App\Http\Requests\Tenant\UpdateOfficialHolidayRequest;
use App\Services\Tenancy\TrashManager;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;

class OfficialHolidayController extends Controller
{
    public function __construct(private readonly TrashManager $trash) {}

    public function index(): View
    {
        $holidays = OfficialHoliday::query()
            ->orderByDesc('start_date')
            ->paginate(config('app.paginate_page'));

        return view('tenant.holidays.index', [
            'holidays' => $holidays,
            'canManage' => auth()->user()?->can('tenant.holidays.manage') ?? false,
        ]);
    }

    public function store(StoreOfficialHolidayRequest $request): RedirectResponse
    {
        $data = $request->validated();

        OfficialHoliday::query()->create([
            ...$data,
            'is_recurring' => $request->boolean('is_recurring'),
        ]);

        flash()->success('تم إضافة العطلة الرسمية بنجاح.');

        return redirect()->route('tenant.holidays.index');
    }

    public function update(UpdateOfficialHolidayRequest $request, OfficialHoliday $officialHoliday): RedirectResponse
    {
        $data = $request->validated();

        $officialHoliday->update([
            ...$data,
            'is_recurring' => $request->boolean('is_recurring'),
        ]);

        flash()->info('تم تحديث العطلة الرسمية بنجاح.');

        return redirect()->route('tenant.holidays.index');
    }

    public function destroy(OfficialHoliday $officialHoliday): RedirectResponse
    {
        abort_unless(auth()->user()?->can('tenant.holidays.manage') ?? false, 403);

        $officialHoliday->delete();

        $this->trash->flashSoftDeleted('تم حذف العطلة الرسمية بنجاح.', 'holidays', $officialHoliday);

        return redirect()->route('tenant.holidays.index');
    }
}
