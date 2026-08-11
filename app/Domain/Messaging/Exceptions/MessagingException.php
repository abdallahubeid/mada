<?php

namespace App\Domain\Messaging\Exceptions;

use RuntimeException;

/**
 * Refusals from the messenger.
 *
 * Messages are Arabic because they surface directly in the tenant UI, the same
 * way TenantReviewException and SettlementException do.
 *
 * Note what `notAParticipant()` deliberately does NOT say: it never confirms
 * that the conversation exists. Distinguishing "no such thread" from "not your
 * thread" would let anyone enumerate conversation ids and learn how many
 * threads their colleagues have.
 */
final class MessagingException extends RuntimeException
{
    public static function recipientUnavailable(): self
    {
        return new self('لا يمكن بدء محادثة مع هذا الموظف — تأكد أنه ما زال على رأس العمل ولديه حساب مفعّل.');
    }

    public static function notAParticipant(): self
    {
        return new self('لا تملك صلاحية الوصول إلى هذه المحادثة.');
    }

    public static function emptyMessage(): self
    {
        return new self('لا يمكن إرسال رسالة فارغة.');
    }

    public static function cannotCreateGroups(): self
    {
        return new self('إنشاء المجموعات متاح للمديرين فقط — يمكنك المشاركة في المجموعات التي تُضاف إليها.');
    }

    public static function groupTitleRequired(): self
    {
        return new self('يجب تسمية المجموعة قبل إنشائها.');
    }

    public static function groupNeedsMembers(): self
    {
        return new self('أضف عضواً واحداً على الأقل إلى المجموعة.');
    }

    public static function unsupportedReaction(): self
    {
        return new self('هذا التفاعل غير مدعوم.');
    }

    public static function cannotPinSystemMessage(): self
    {
        return new self('لا يمكن تثبيت رسائل النظام.');
    }

    /**
     * Deliberately absolute: no role overrides this. A thread stops being a
     * reliable record of who said what the moment someone else can remove it.
     */
    public static function cannotDeleteOthersMessages(): self
    {
        return new self('يمكنك حذف رسائلك أنت فقط.');
    }
}
