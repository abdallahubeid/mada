<?php

use App\Domain\Tenancy\ApprovableCatalog;
use App\Domain\Tenancy\Enums\ApprovalStatus;
use App\Domain\Tenancy\Models\Approval;
use App\Domain\Tenancy\Models\Employee;
use App\Domain\Tenancy\Models\LeaveRequest;
use App\Domain\Tenancy\Models\LeaveType;
use App\Domain\Tenancy\Models\Tenant;
use App\Domain\Tenancy\TenantContext;
use App\Domain\Tenancy\TenantPermissionCatalog;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;

uses(RefreshDatabase::class);

/**
 * @return array{0: LeaveRequest, 1: int}
 */
function makeLeaveRequestForApproval(int $tenantId): array
{
    $employee = Employee::factory()->create(['tenant_id' => $tenantId]);
    $leaveType = LeaveType::factory()->create(['tenant_id' => $tenantId]);

    $leave = LeaveRequest::factory()->create([
        'tenant_id' => $tenantId,
        'employee_id' => $employee->id,
        'leave_type_id' => $leaveType->id,
    ]);

    return [$leave, $employee->id];
}

test('approval stores a short morph alias, never a fully qualified class name', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$leave] = makeLeaveRequestForApproval($user->tenant_id);

    $approval = $leave->approvals()->create(['requested_by' => $user->id]);

    // Read raw, bypassing Eloquent's morph resolution entirely (BR-902).
    $storedType = DB::table('approvals')->where('id', $approval->id)->value('approvable_type');

    expect($storedType)->toBe(ApprovableCatalog::LEAVE_REQUEST)
        ->and($storedType)->not->toContain('\\')
        ->and($storedType)->not->toBe(LeaveRequest::class);
});

test('the morph map is registered non-enforcing so unmapped models still write', function () {
    // Guards ADR-08: enforceMorphMap() would throw on write for any unmapped
    // model, breaking notifications, whose notifiable_type still stores FQCNs.
    expect(Relation::requiresMorphMap())->toBeFalse()
        ->and(Relation::getMorphedModel(ApprovableCatalog::LEAVE_REQUEST))->toBe(LeaveRequest::class);
});

test('approvable resolves back to the originating subject through the alias', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$leave] = makeLeaveRequestForApproval($user->tenant_id);

    $approval = $leave->approvals()->create(['requested_by' => $user->id]);
    $resolved = $approval->fresh()->approvable;

    expect($resolved)->toBeInstanceOf(LeaveRequest::class)
        ->and($resolved->id)->toBe($leave->id);
});

test('a leave request exposes its approval history and its single open approval', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$leave] = makeLeaveRequestForApproval($user->tenant_id);

    $decided = $leave->approvals()->create([
        'requested_by' => $user->id,
        'status' => ApprovalStatus::Rejected,
        'decided_by' => $user->id,
        'decided_at' => now(),
        'reason' => 'مستندات ناقصة',
    ]);
    $open = $leave->approvals()->create(['requested_by' => $user->id]);

    $leave->refresh();

    expect($leave->approvals)->toHaveCount(2)
        ->and($leave->currentApproval->id)->toBe($open->id)
        ->and($leave->currentApproval->id)->not->toBe($decided->id);
});

test('approval defaults to a single pending level and reports terminality correctly', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$leave] = makeLeaveRequestForApproval($user->tenant_id);

    $approval = $leave->approvals()->create(['requested_by' => $user->id]);

    expect($approval->status)->toBe(ApprovalStatus::Pending)
        ->and($approval->level)->toBe(1)
        ->and($approval->current_level)->toBe(1)
        ->and($approval->isFinalLevel())->toBeTrue()
        ->and($approval->isOpen())->toBeTrue()
        ->and($approval->status->isTerminal())->toBeFalse();

    foreach ([ApprovalStatus::Approved, ApprovalStatus::Rejected, ApprovalStatus::Cancelled] as $terminal) {
        expect($terminal->isTerminal())->toBeTrue();
    }
});

test('a mid-chain level is not final so the chain can advance', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$leave] = makeLeaveRequestForApproval($user->tenant_id);

    $approval = $leave->approvals()->create([
        'requested_by' => $user->id,
        'level' => 2,
        'current_level' => 1,
    ]);

    expect($approval->isFinalLevel())->toBeFalse();

    $approval->update(['current_level' => 2]);

    expect($approval->fresh()->isFinalLevel())->toBeTrue();
});

test('approvals are invisible across tenants under the global scope', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    $tenantA = $user->tenant_id;
    [$leave] = makeLeaveRequestForApproval($tenantA);

    $approval = $leave->approvals()->create(['requested_by' => $user->id]);

    expect(Approval::query()->count())->toBe(1)
        ->and($approval->tenant_id)->toBe($tenantA);

    $tenantB = Tenant::factory()->create();
    app(TenantContext::class)->setTenant($tenantB);

    expect(Approval::query()->count())->toBe(0)
        ->and(Approval::query()->find($approval->id))->toBeNull();

    app(TenantContext::class)->setTenant(null);
});

test('tenant id is stamped from context without being passed explicitly', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$leave] = makeLeaveRequestForApproval($user->tenant_id);

    $approval = $leave->approvals()->create(['requested_by' => $user->id]);

    expect($approval->tenant_id)->toBe($user->tenant_id);
});

test('approvals soft delete rather than disappear', function () {
    $user = actingAsTenantUser(TenantPermissionCatalog::ROLE_OWNER, ['status' => 'active']);
    [$leave] = makeLeaveRequestForApproval($user->tenant_id);

    $approval = $leave->approvals()->create(['requested_by' => $user->id]);
    $approval->delete();

    expect(Approval::query()->count())->toBe(0)
        ->and(Approval::withTrashed()->count())->toBe(1);
});

test('the catalog reserves aliases for subjects that do not exist yet', function () {
    expect(ApprovableCatalog::aliases())->toBe([
        'leave_request',
        'payroll_run',
        'expense',
        'offboarding_settlement',
    ]);

    // Only mapped subjects resolve; reserved names join the map as they land.
    expect(ApprovableCatalog::morphMap())->toHaveKey(ApprovableCatalog::LEAVE_REQUEST)
        ->and(ApprovableCatalog::morphMap())->not->toHaveKey(ApprovableCatalog::PAYROLL_RUN);
});
