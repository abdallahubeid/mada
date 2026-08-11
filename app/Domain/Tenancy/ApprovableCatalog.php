<?php

namespace App\Domain\Tenancy;

use App\Domain\Finance\Models\Expense;
use App\Domain\Tenancy\Models\LeaveRequest;

/**
 * Morph-map aliases for polymorphic approval subjects (BR-902).
 *
 * `approvals.approvable_type` stores these short aliases, never a
 * fully-qualified class name, so a namespace move can never orphan a
 * financial record.
 *
 * Registered NON-ENFORCING via Relation::morphMap() in AppServiceProvider.
 * Do not promote to enforceMorphMap() until every polymorphic model in the
 * app is mapped and `notifications.notifiable_type` is backfilled off FQCNs —
 * enforcement throws on write for any unmapped model, which would break every
 * new notification (ADR-08).
 */
final class ApprovableCatalog
{
    public const LEAVE_REQUEST = 'leave_request';

    public const PAYROLL_RUN = 'payroll_run';

    public const EXPENSE = 'expense';

    public const OFFBOARDING_SETTLEMENT = 'offboarding_settlement';

    /**
     * Aliases whose subject model exists today.
     *
     * PAYROLL_RUN and OFFBOARDING_SETTLEMENT remain reserved: both carry their
     * own maker-checker columns rather than routing through `approvals`, since
     * their approval also performs a lock and a snapshot that the generic
     * engine has no concept of. They join the map only if that changes.
     *
     * @return array<string, class-string>
     */
    public static function morphMap(): array
    {
        return [
            self::LEAVE_REQUEST => LeaveRequest::class,
            self::EXPENSE => Expense::class,
        ];
    }

    /**
     * Every alias the Approval Engine recognises, including reserved ones.
     *
     * @return list<string>
     */
    public static function aliases(): array
    {
        return [
            self::LEAVE_REQUEST,
            self::PAYROLL_RUN,
            self::EXPENSE,
            self::OFFBOARDING_SETTLEMENT,
        ];
    }
}
