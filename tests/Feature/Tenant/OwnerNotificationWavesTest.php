<?php

use App\Domain\Tenancy\Enums\AnnouncementType;
use App\Domain\Tenancy\Enums\ApplicationStatus;
use App\Domain\Tenancy\Enums\AssetCategory;
use App\Domain\Tenancy\Enums\AssetCondition;
use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\Enums\ContractStatus;
use App\Domain\Tenancy\Enums\ContractType;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\TenantStatus;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\EmployeeContract;
use App\Domain\Tenancy\Models\JobApplication;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\Models\WorkCalendar;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Notifications\Tenant\ApplicantAcceptedNotification;
use App\Notifications\Tenant\AssetAssignedNotification;
use App\Notifications\Tenant\AttendanceMarkedLateNotification;
use App\Notifications\Tenant\ContractLifecycleNotification;
use App\Notifications\Tenant\EmployeeCreatedNotification;
use App\Notifications\Tenant\SubscriptionLimitNotification;
use App\Notifications\Tenant\TeamMemberAccessNotification;
use App\Notifications\Tenant\UrgentAnnouncementPublishedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Notification;

uses(RefreshDatabase::class);

test('wave 2 operational triggers notify the owner', function () {
    Notification::fake();
    Mail::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'plan' => 'startup',
    ]);

    $this->post(route('hr.employees.store'), [
        'first_name' => 'Laila',
        'last_name' => 'Hassan',
        'job_title' => 'Designer',
        'joining_date' => now()->toDateString(),
        'status' => EmployeeStatus::Active->value,
        'department_id' => null,
        'manager_id' => null,
        'create_user_account' => '0',
    ])->assertRedirect();

    Notification::assertSentTo($owner, EmployeeCreatedNotification::class);

    $employee = Employee::query()->firstOrFail();

    $asset = Asset::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'status' => AssetStatus::Available,
        'category' => AssetCategory::Laptop,
        'asset_code' => 'AST-200',
    ]);

    $this->post(route('tenant.assets.assign', $asset), [
        'employee_id' => $employee->id,
        'condition_on_assign' => AssetCondition::New->value,
    ])->assertRedirect();

    Notification::assertSentTo($owner, AssetAssignedNotification::class);

    $job = JobPosting::factory()->published()->create([
        'tenant_id' => $owner->tenant_id,
        'title' => 'Frontend Dev',
    ]);

    $application = JobApplication::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'job_posting_id' => $job->id,
        'applicant_name' => 'Nada Ali',
        'status' => ApplicationStatus::Accepted,
    ]);

    $this->post(route('hr.applications.convert', $application))->assertRedirect();

    Notification::assertSentTo($owner, ApplicantAcceptedNotification::class);

    WorkCalendar::query()->create([
        'tenant_id' => $owner->tenant_id,
        'name' => 'Default',
        'work_start_time' => '09:00:00',
        'work_end_time' => '17:00:00',
        'grace_period_minutes' => 0,
        'weekend_days' => [5, 6],
        'working_days' => [0, 1, 2, 3, 4],
        'holidays' => [],
    ]);

    Carbon::setTestNow(Carbon::parse('2026-08-03 10:30:00'));

    $this->post(route('hr.attendance.check-in'), [
        'employee_id' => $employee->id,
    ])->assertRedirect();

    Notification::assertSentTo($owner, AttendanceMarkedLateNotification::class);

    Carbon::setTestNow();

    $this->post(route('tenant.announcements.store'), [
        'title' => 'إخلاء طارئ',
        'content' => 'يُرجى مغادرة المبنى فوراً.',
        'type' => AnnouncementType::Urgent->value,
        'published_at' => now()->format('Y-m-d H:i:s'),
        'is_pinned' => '1',
    ])->assertRedirect();

    Notification::assertSentTo($owner, UrgentAnnouncementPublishedNotification::class);

    $this->post(route('team.store'), [
        'name' => 'Sara Team',
        'email' => 'sara.team@example.test',
        'department_id' => null,
        'role' => TenantPermissionCatalog::ROLE_EMPLOYEE,
        'permissions' => ['tenant.dashboard.view'],
        'auto_generate_password' => '1',
        'password' => null,
        'password_confirmation' => null,
    ])->assertRedirect();

    Notification::assertSentTo($owner, TeamMemberAccessNotification::class);

    $member = User::query()->where('email', 'sara.team@example.test')->firstOrFail();

    $this->patch(route('team.toggle-status', $member))->assertRedirect();

    Notification::assertSentTo($owner, TeamMemberAccessNotification::class, function (TeamMemberAccessNotification $notification): bool {
        return $notification->action === 'deactivated';
    });
});

test('wave 3 contract billing notify the owner', function () {
    Notification::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'plan' => 'startup',
    ]);

    $employee = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);

    $this->post(route('hr.contracts.store'), [
        'employee_id' => $employee->id,
        'contract_type' => ContractType::FullTime->value,
        'start_date' => now()->toDateString(),
        'end_date' => now()->addYear()->toDateString(),
        'probation_end_date' => null,
        'status' => ContractStatus::Active->value,
        'notes' => null,
    ])->assertRedirect();

    Notification::assertSentTo($owner, ContractLifecycleNotification::class, function (ContractLifecycleNotification $notification): bool {
        return $notification->action === 'created';
    });

    $contract = EmployeeContract::query()->firstOrFail();

    $this->put(route('hr.contracts.update', $contract), [
        'employee_id' => $employee->id,
        'contract_type' => ContractType::FullTime->value,
        'start_date' => $contract->start_date->toDateString(),
        'end_date' => $contract->end_date?->toDateString(),
        'probation_end_date' => null,
        'status' => ContractStatus::Terminated->value,
        'notes' => 'Ended early',
    ])->assertRedirect();

    Notification::assertSentTo($owner, ContractLifecycleNotification::class, function (ContractLifecycleNotification $notification): bool {
        return $notification->action === 'terminated';
    });

    // Force employee limit (startup default 10) then block the 11th create.
    Employee::factory()->count(9)->create(['tenant_id' => $owner->tenant_id]);

    $this->from(route('hr.employees.create'))
        ->post(route('hr.employees.store'), [
            'first_name' => 'Blocked',
            'last_name' => 'Hire',
            'job_title' => 'Intern',
            'joining_date' => now()->toDateString(),
            'status' => EmployeeStatus::Active->value,
            'department_id' => null,
            'manager_id' => null,
            'create_user_account' => '0',
        ])
        ->assertRedirect(route('hr.employees.create'));

    Notification::assertSentTo($owner, SubscriptionLimitNotification::class, function (SubscriptionLimitNotification $notification): bool {
        return $notification->action === 'reached';
    });
});

test('wave 4 scheduled commands notify owners for expiring contracts and renewals', function () {
    Notification::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $tenant = Tenant::query()->findOrFail($owner->tenant_id);
    $tenant->update([
        'status' => TenantStatus::Active,
        'renews_at' => now()->addDays(3),
    ]);

    $employee = Employee::factory()->create(['tenant_id' => $tenant->id]);

    EmployeeContract::factory()->create([
        'tenant_id' => $tenant->id,
        'employee_id' => $employee->id,
        'status' => ContractStatus::Active,
        'start_date' => now()->subYear(),
        'end_date' => now()->addDays(10),
    ]);

    $this->artisan('tenant:send-expiring-contract-notifications')->assertSuccessful();
    $this->artisan('tenant:send-subscription-renewal-notifications')->assertSuccessful();

    Notification::assertSentTo($owner, ContractLifecycleNotification::class, function (ContractLifecycleNotification $notification): bool {
        return $notification->action === 'expiring';
    });

    Notification::assertSentTo($owner, SubscriptionLimitNotification::class, function (SubscriptionLimitNotification $notification): bool {
        return $notification->action === 'renewal' && $notification->daysRemaining === 3;
    });

    // Idempotent within the same day via cache keys.
    $this->artisan('tenant:send-expiring-contract-notifications')->assertSuccessful();
    Notification::assertSentToTimes($owner, ContractLifecycleNotification::class, 1);
});

test('realtime drawer payload shape remains dual-channel compatible', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $notification = new EmployeeCreatedNotification(
        Employee::factory()->create(['tenant_id' => $owner->tenant_id, 'first_name' => 'Live', 'last_name' => 'Push'])
    );
    $notification->id = 'test-uuid';

    $broadcast = $notification->toBroadcast($owner)->data;

    expect($notification->via($owner))->toBe(['database', 'broadcast'])
        ->and($notification->broadcastAs())->toBe('TenantNotificationCreated')
        ->and($broadcast)->toHaveKeys(['id', 'title', 'message', 'url', 'icon', 'severity', 'type', 'unread_count', 'sound'])
        ->and($broadcast['sound'])->toBeTrue()
        ->and($owner->receivesBroadcastNotificationsOn())->toBe(
            'tenant.'.$owner->tenant_id.'.notifications.'.$owner->id
        );
});
