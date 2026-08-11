<?php

namespace App\Domain\Tenancy\Enums;

/**
 * The lifecycle states a tenant can occupy.
 *
 * The binding state machine defined in docs/ARCHITECTURE.md §3:
 *
 *   pending_verification -> pending_approval -> active -> suspended -> cancelled
 *                                           \-> rejected
 *
 * `rejected` was added 2026-08-09 (ADR-04 amended). It is deliberately NOT
 * folded into `cancelled`: cancellation is a customer-initiated exit, rejection
 * is a Super Admin refusing an application. Conflating them would make the
 * lifecycle unable to answer "how many applicants did we turn away", which is
 * the question the state machine exists to support — and would attach a
 * `rejection_reason` to records nobody rejected.
 *
 * Transition rules (BR-201 through BR-206) are enforced by the onboarding and
 * Super Admin approval flows, not by this enum itself.
 */
enum TenantStatus: string
{
    case PendingVerification = 'pending_verification';
    case PendingApproval = 'pending_approval';
    case Active = 'active';
    case Rejected = 'rejected';
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
            self::Rejected => 'Rejected',
            self::Suspended => 'Suspended',
            self::Cancelled => 'Cancelled',
        };
    }

    /**
     * Arabic label for the Platform Console and tenant-facing surfaces.
     */
    public function arabicLabel(): string
    {
        return match ($this) {
            self::PendingVerification => 'بانتظار التحقق',
            self::PendingApproval => 'بانتظار الاعتماد',
            self::Active => 'نشط',
            self::Rejected => 'مرفوض',
            self::Suspended => 'موقوف',
            self::Cancelled => 'ملغى',
        };
    }

    /**
     * States in which the tenant may reach onboarding but no operational
     * module — the account exists and is being provisioned or reviewed.
     */
    public function isOnboarding(): bool
    {
        return in_array($this, [self::PendingVerification, self::PendingApproval], true);
    }

    /**
     * Terminal refusals and exits. No route into the product, and no path back
     * without Super Admin intervention.
     */
    public function isTerminated(): bool
    {
        return in_array($this, [self::Rejected, self::Cancelled], true);
    }

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }
}
