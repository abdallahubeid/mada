<?php

namespace App\Domain\Messaging\Enums;

/**
 * `System` is not sent by a person — it is the thread narrating itself
 * ("أضاف فلان فلاناً إلى المجموعة"). It renders centred and unstyled, carries
 * no reactions and no reply affordance, and its `sender_id` may be null.
 */
enum MessageType: string
{
    case Text = 'text';
    case Image = 'image';
    case File = 'file';
    case System = 'system';

    public function isAttachment(): bool
    {
        return in_array($this, [self::Image, self::File], true);
    }
}
