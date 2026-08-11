<?php

use App\Domain\Tenancy\Enums\AnnouncementType;
use App\Domain\Tenancy\Enums\AttendanceStatus;
use App\Domain\Tenancy\Models\Announcement;
use App\Domain\Tenancy\Models\Attendance;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\OfficialHoliday;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;

uses(RefreshDatabase::class);

test('owner can publish announcements and they render on the dashboard', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->post(route('tenant.announcements.store'), [
        'title' => 'اجتماع الفريق الأسبوعي',
        'content' => 'يعقد الاجتماع غداً الساعة العاشرة.',
        'type' => AnnouncementType::Event->value,
        'published_at' => now()->subMinute()->format('Y-m-d H:i:s'),
        'expires_at' => now()->addDays(3)->format('Y-m-d H:i:s'),
        'is_pinned' => '1',
    ])->assertRedirect(route('tenant.announcements.index'));

    $announcement = Announcement::query()->first();

    expect($announcement)->not->toBeNull()
        ->and($announcement->title)->toBe('اجتماع الفريق الأسبوعي')
        ->and($announcement->is_pinned)->toBeTrue()
        ->and($announcement->created_by)->toBe($user->id);

    $this->get(route('tenant.announcements.index'))
        ->assertOk()
        ->assertSee('اجتماع الفريق الأسبوعي')
        ->assertSee('نشط');

    $this->get(route('dashboard'))
        ->assertOk()
        ->assertSee('data-testid="announcements-banner"', false)
        ->assertSee('اجتماع الفريق الأسبوعي')
        ->assertSee('يعقد الاجتماع غداً الساعة العاشرة.');
});

test('holidays correctly exclude days from leave calculations', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    OfficialHoliday::factory()->create([
        'tenant_id' => auth()->user()->tenant_id,
        'name' => 'عيد وطني',
        'start_date' => '2026-09-23',
        'end_date' => '2026-09-23',
        'is_recurring' => false,
    ]);

    // 22, 23, 24 Sep → exclude 23 → 2 days
    expect(LeaveRequest::calculateDaysCount('2026-09-22', '2026-09-24'))->toBe(2);

    OfficialHoliday::factory()->create([
        'tenant_id' => auth()->user()->tenant_id,
        'name' => 'رأس السنة',
        'start_date' => '2026-01-01',
        'end_date' => '2026-01-01',
        'is_recurring' => true,
    ]);

    expect(LeaveRequest::calculateDaysCount('2027-01-01', '2027-01-01'))->toBe(0)
        ->and(LeaveRequest::calculateDaysCount('2027-01-01', '2027-01-02'))->toBe(1);

    $this->post(route('tenant.holidays.store'), [
        'name' => 'عطلة تجريبية',
        'start_date' => '2026-10-01',
        'end_date' => '2026-10-02',
        'is_recurring' => '0',
        'notes' => 'اختبار',
    ])->assertRedirect(route('tenant.holidays.index'));

    expect(OfficialHoliday::query()->where('name', 'عطلة تجريبية')->exists())->toBeTrue();
});

test('shift grace period settings update successfully', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->get(route('settings.work-schedule'))
        ->assertOk()
        ->assertSee('جدول العمل والورديات');

    $this->put(route('settings.work-schedule.update'), [
        'work_start_time' => '08:30',
        'work_end_time' => '16:30',
        'grace_period_minutes' => 20,
        'weekend_days' => [5, 6],
    ])->assertRedirect(route('settings.work-schedule'));

    $calendar = WorkCalendar::query()->first();

    expect($calendar)->not->toBeNull()
        ->and($calendar->workStartTimeLabel())->toBe('08:30')
        ->and($calendar->workEndTimeLabel())->toBe('16:30')
        ->and($calendar->grace_period_minutes)->toBe(20)
        ->and($calendar->weekend_days)->toBe([5, 6])
        ->and($calendar->working_days)->toBe([0, 1, 2, 3, 4])
        ->and($calendar->lateThreshold())->toBe('08:50');

    Carbon::setTestNow(Carbon::parse('2026-08-02 08:45:00'));
    expect(Attendance::resolveCheckInStatus(now()))->toBe(AttendanceStatus::Present);

    Carbon::setTestNow(Carbon::parse('2026-08-02 08:51:00'));
    expect(Attendance::resolveCheckInStatus(now()))->toBe(AttendanceStatus::Late);

    Carbon::setTestNow();
});

test('employee cannot manage announcements or holidays', function () {
    actingAsTenantUser(TenantPermissionCatalog::ROLE_EMPLOYEE, ['status' => 'active']);

    $this->get(route('tenant.announcements.index'))->assertForbidden();
    $this->get(route('tenant.holidays.index'))->assertForbidden();
    $this->get(route('settings.work-schedule'))->assertForbidden();
});
