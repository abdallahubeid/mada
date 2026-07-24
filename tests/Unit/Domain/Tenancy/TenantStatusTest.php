<?php

use App\Domain\Tenancy\Enums\TenantStatus;

test('the tenant status enum defines the full 5-state lifecycle', function () {
    expect(TenantStatus::PendingVerification->value)->toBe('pending_verification')
        ->and(TenantStatus::PendingApproval->value)->toBe('pending_approval')
        ->and(TenantStatus::Active->value)->toBe('active')
        ->and(TenantStatus::Suspended->value)->toBe('suspended')
        ->and(TenantStatus::Cancelled->value)->toBe('cancelled');
});

test('each tenant status has a human-readable label', function (TenantStatus $status, string $label) {
    expect($status->label())->toBe($label);
})->with([
    [TenantStatus::PendingVerification, 'Pending Verification'],
    [TenantStatus::PendingApproval, 'Pending Approval'],
    [TenantStatus::Active, 'Active'],
    [TenantStatus::Suspended, 'Suspended'],
    [TenantStatus::Cancelled, 'Cancelled'],
]);
