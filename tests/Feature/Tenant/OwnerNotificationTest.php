<?php

use App\Broadcasting\TenantNotificationChannel;
use App\Domain\Tenancy\Enums\AssetCategory;
use App\Domain\Tenancy\Enums\AssetCondition;
use App\Domain\Tenancy\Enums\AssetStatus;
use App\Domain\Tenancy\Enums\EmployeeStatus;
use App\Domain\Tenancy\Enums\EmploymentType;
use App\Domain\Tenancy\Enums\JobPostingStatus;
use App\Domain\Tenancy\Models\Asset;
use App\Domain\Tenancy\Models\AssetAssignment;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\JobPosting;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\TenantPortalSetting;
use App\Domain\Tenancy\TenantPermissionCatalog;
use App\Models\User;
use App\Notifications\Tenant\AssetReturnedNotification;
use App\Notifications\Tenant\EmployeeStatusChangedNotification;
use App\Notifications\Tenant\NewJobApplicationNotification;
use App\Notifications\Tenant\NewLeaveRequestNotification;
use App\Notifications\Tenant\RolePermissionsChangedNotification;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Facades\Storage;
use Spatie\Permission\PermissionRegistrar;

uses(RefreshDatabase::class);

test('leave request submission notifies only tenant owners via database channel', function () {
    Notification::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employeeUser = User::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'is_active' => true,
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($owner->tenant_id);
    $employeeUser->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);

    $employee = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);

    $leaveType = LeaveType::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'name' => 'سنوية',
        'annual_days' => 21,
        'requires_approval' => true,
    ]);

    $this->post(route('hr.leaves.requests.store'), [
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
        'start_date' => now()->addDays(10)->toDateString(),
        'end_date' => now()->addDays(12)->toDateString(),
        'reason' => 'سفر عائلي',
    ])->assertRedirect();

    Notification::assertSentTo($owner, NewLeaveRequestNotification::class, function (NewLeaveRequestNotification $notification) use ($owner): bool {
        $payload = $notification->toArray($owner);

        expect($payload)->toHaveKeys(['title', 'message', 'url', 'icon', 'severity', 'type'])
            ->and($payload['type'])->toBe('leave.submitted')
            ->and($payload['severity'])->toBe('high')
            ->and($notification->via($owner))->toBe(['database', 'broadcast'])
            ->and($notification->broadcastAs())->toBe('TenantNotificationCreated');

        $broadcast = $notification->toBroadcast($owner)->data;

        expect($broadcast)->toHaveKeys(['title', 'message', 'url', 'icon', 'severity', 'type', 'unread_count', 'sound'])
            ->and($broadcast['sound'])->toBeTrue();

        return true;
    });

    Notification::assertNotSentTo($employeeUser, NewLeaveRequestNotification::class);
});

test('employee suspension and deletion notify the owner', function () {
    Notification::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'first_name' => 'Mona',
        'last_name' => 'Said',
        'job_title' => 'Analyst',
        'joining_date' => now()->subYear()->toDateString(),
        'status' => EmployeeStatus::Active,
    ]);

    $this->put(route('hr.employees.update', $employee), [
        'first_name' => 'Mona',
        'last_name' => 'Said',
        'job_title' => 'Analyst',
        'joining_date' => $employee->joining_date->toDateString(),
        'status' => EmployeeStatus::Suspended->value,
        'department_id' => null,
        'manager_id' => null,
        'remove_avatar' => '0',
        'remove_cv' => '0',
        'create_user_account' => '0',
    ])->assertRedirect();

    Notification::assertSentTo($owner, EmployeeStatusChangedNotification::class);

    $other = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'first_name' => 'Delete',
        'last_name' => 'Me',
        'job_title' => 'Temp',
        'joining_date' => now()->subMonth()->toDateString(),
        'status' => EmployeeStatus::Active,
    ]);

    $this->delete(route('hr.employees.destroy', $other))->assertRedirect();

    Notification::assertSentToTimes($owner, EmployeeStatusChangedNotification::class, 2);
});

test('asset return notifies the owner with broadcast metadata', function () {
    Notification::fake();

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $employee = Employee::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'status' => EmployeeStatus::Active,
    ]);

    $asset = Asset::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'name' => 'Laptop X',
        'asset_code' => 'AST-100',
        'category' => AssetCategory::Laptop,
        'status' => AssetStatus::Assigned,
    ]);

    AssetAssignment::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'asset_id' => $asset->id,
        'employee_id' => $employee->id,
        'assigned_by' => $owner->id,
        'returned_at' => null,
        'condition_on_assign' => AssetCondition::Good,
    ]);

    $this->post(route('tenant.assets.return', $asset), [
        'condition_on_return' => AssetCondition::Fair->value,
        'status' => AssetStatus::UnderMaintenance->value,
        'notes' => 'شاشة مكسورة',
    ])->assertRedirect(route('tenant.assets.index'));

    Notification::assertSentTo($owner, AssetReturnedNotification::class, function (AssetReturnedNotification $notification) use ($owner): bool {
        $payload = $notification->toArray($owner);

        return $payload['type'] === 'asset.returned'
            && $payload['severity'] === 'high'
            && str_contains($payload['message'], 'AST-100');
    });
});

test('public job application notifies the tenant owner', function () {
    Notification::fake();
    Storage::fake('custom');

    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, [
        'status' => 'active',
        'slug' => 'notify-hire',
        'name' => 'Notify Hire',
    ]);

    TenantPortalSetting::query()->create(TenantPortalSetting::defaultAttributes($owner->tenant));

    $job = JobPosting::factory()->published()->create([
        'tenant_id' => $owner->tenant_id,
        'title' => 'QA Engineer',
        'employment_type' => EmploymentType::FullTime,
        'status' => JobPostingStatus::Published,
    ]);

    auth()->logout();

    $this->post(route('portal.jobs.apply', ['notify-hire', $job->slug]), [
        'applicant_name' => 'Rami Nasser',
        'email' => 'rami@example.test',
        'phone' => '+966500123123',
        'cover_letter' => 'Ready to join.',
        'cv' => UploadedFile::fake()->create('cv.pdf', 100, 'application/pdf'),
    ])->assertRedirect();

    Notification::assertSentTo($owner, NewJobApplicationNotification::class);
});

test('role permission changes notify the owner and persist when not faked', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $this->post(route('roles.store'), [
        'name' => 'Ops Viewer',
        'permissions' => ['tenant.dashboard.view'],
    ])->assertRedirect(route('roles.index'));

    $notification = $owner->notifications()->first();

    expect($notification)->not->toBeNull()
        ->and($notification->type)->toBe(RolePermissionsChangedNotification::class)
        ->and($notification->data['type'])->toBe('role.created')
        ->and($notification->data['title'])->toBe('إنشاء دور')
        ->and($notification->read_at)->toBeNull();

    $this->getJson(route('tenant.notifications.index'))
        ->assertOk()
        ->assertJsonPath('unread_count', 1)
        ->assertJsonPath('notifications.0.title', 'إنشاء دور');

    $this->postJson(route('tenant.notifications.read', $notification->id))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect($notification->fresh()->read_at)->not->toBeNull();

    $this->post(route('roles.store'), [
        'name' => 'Ops Editor',
        'permissions' => ['tenant.dashboard.view'],
    ])->assertRedirect();

    $this->postJson(route('tenant.notifications.read-all'))
        ->assertOk()
        ->assertJsonPath('unread_count', 0);

    expect($owner->fresh()->unreadNotifications()->count())->toBe(0);
});

test('private tenant notification channel authorizes matching owner only', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    $stranger = User::factory()->create([
        'tenant_id' => $owner->tenant_id,
        'is_active' => true,
    ]);
    app(PermissionRegistrar::class)->setPermissionsTeamId($owner->tenant_id);
    $stranger->assignRole(TenantPermissionCatalog::ROLE_EMPLOYEE);

    $channel = app(TenantNotificationChannel::class);

    expect($channel->join($owner, $owner->tenant_id, $owner->id))->toBeTrue()
        ->and($channel->join($stranger, $owner->tenant_id, $owner->id))->toBeFalse()
        ->and($channel->join($stranger, $owner->tenant_id, $stranger->id))->toBeTrue()
        ->and($channel->join($owner, $owner->tenant_id + 1, $owner->id))->toBeFalse();
});

test('owner broadcast channel name matches receivesBroadcastNotificationsOn', function () {
    $owner = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);

    expect($owner->receivesBroadcastNotificationsOn())
        ->toBe('tenant.'.$owner->tenant_id.'.notifications.'.$owner->id);
});
