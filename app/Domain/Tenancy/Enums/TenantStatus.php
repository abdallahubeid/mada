<?php

namespace App\Domain\Tenancy\Enums;

/**
 * The lifecycle states a tenant can occupy.
 *
 * This is the binding 5-state machine defined in docs/ARCHITECTURE.md §3:
 *
 *   pending_verification -> pending_approval -> active -> suspended -> cancelled
 *
 * Transition rules (BR-201 through BR-206) are enforced by the onboarding
 * and Super Admin approval flows, not by this enum itself.
 */
enum TenantStatus: string
{
    case PendingVerification = 'pending_verification';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Suspended = 'suspended';
    case Cancelled = 'cancelled';

    /**
     * Human-readable label for UI display (status badges, admin tables).
     */
    public function label(): string
    {
        return match ($this) {
            self::PendingVerification => 'Pending Verification',
            self::PendingApproval => 'Pending Approval',
            self::Active => 'Active',
            self::Suspended => 'Suspended',
            self::Cancelled => 'Cancelled',
        };
    }
}
