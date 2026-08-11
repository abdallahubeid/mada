<?php

namespace App\Domain\Tenancy\Exceptions;

use App\Domain\Tenancy\Enums\TenantStatus;
use RuntimeException;

/**
 * Refusals from the Super Admin tenant review flow.
 *
 * Messages are Arabic because they surface directly in the Platform Console
 * flash, the same way SettlementException and PayrollRunException do in the
 * tenant app.
 */
final class TenantReviewException extends RuntimeException
{
    public static function alreadyActive(string $name): self
    {
        return new self("المؤسسة «{$name}» مفعّلة بالفعل ولا تحتاج إلى مراجعة.");
    }

    public static function notAwaitingReview(string $name, TenantStatus $status): self
    {
        return new self(
            "لا يمكن مراجعة المؤسسة «{$name}» في حالتها الحالية ({$status->arabicLabel()}) — المراجعة متاحة فقط للطلبات بانتظار الاعتماد."
        );
    }

    public static function reasonRequired(): self
    {
        return new self('يجب إدخال سبب الرفض قبل إتمام العملية.');
    }

    /**
     * Only a live account can be suspended. Suspending a pending registration
     * would skip the review it still needs, and suspending an already-refused
     * one would overwrite a decision that was already made.
     */
    public static function notSuspendable(string $name, TenantStatus $status): self
    {
        return new self(
            "لا يمكن إيقاف المؤسسة «{$name}» في حالتها الحالية ({$status->arabicLabel()}) — الإيقاف متاح فقط للمؤسسات النشطة."
        );
    }

    public static function suspensionReasonRequired(): self
    {
        return new self('يجب إدخال سبب الإيقاف قبل إتمام العملية.');
    }

    /**
     * Reactivation is the inverse of suspension only. A cancelled or rejected
     * tenant returning to `active` through this path would bypass the review
     * and billing decisions those states represent.
     */
    public static function notSuspended(string $name, TenantStatus $status): self
    {
        return new self(
            "لا يمكن إعادة تفعيل المؤسسة «{$name}» في حالتها الحالية ({$status->arabicLabel()}) — إعادة التفعيل متاحة فقط للمؤسسات الموقوفة."
        );
    }
}
